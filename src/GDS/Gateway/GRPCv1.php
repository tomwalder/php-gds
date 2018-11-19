<?php
/**
 * Copyright 2015 Tom Walder
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace GDS\Gateway;

use GDS\Entity;
use GDS\Exception\Contention;
use GDS\Mapper;
use GDS\Mapper\ProtoBufGQLParser;
use Google\ApiCore\ApiException;
use Google\Protobuf\Timestamp;
use Google\Protobuf\Internal\RepeatedField;
use Google\Cloud\Datastore\V1\DatastoreClient;
use Google\Cloud\Datastore\V1\Entity as GRPC_Entity;
use Google\Cloud\Datastore\V1\Key;
use Google\Cloud\Datastore\V1\Key\PathElement as KeyPathElement;
use Google\Cloud\Datastore\V1\PartitionId;
use Google\Cloud\Datastore\V1\CommitRequest_Mode;
use Google\Cloud\Datastore\V1\Mutation;
use Google\Cloud\Datastore\V1\ReadOptions;
use Google\Cloud\Datastore\V1\GqlQuery;
use Google\Cloud\Datastore\V1\GqlQueryParameter;
use Google\Cloud\Datastore\V1\Value;

/**
 * gRPC Datastore Gateway (v1)
 * based on ProtoBuf by Tom Walder <twalder@gmail.com>
 *
 * @author Samuel Melrose <sam@infitialis.com>
 * @author Tom Walder <twalder@gmail.com>
 * @package GDS\Gateway
 */
class GRPCv1 extends \GDS\Gateway
{

    /**
     * gRPC Client
     */
    protected static $grpc_client;

    /**
     * Set up the dataset and optional namespace,
     * plus the gRPC client.
     *
     * @param null|string $str_dataset
     * @param null|string $str_namespace
     * @throws \Exception
     */
    public function __construct($str_dataset = null, $str_namespace = null)
    {
        if(null === $str_dataset) {
            if(isset($_SERVER['GOOGLE_CLOUD_PROJECT'])) {
                $this->str_dataset_id = $_SERVER['GOOGLE_CLOUD_PROJECT'];
            } else {
                throw new \Exception('Could not determine DATASET, please pass to ' . get_class($this) . '::__construct()');
            }
        } else {
            $this->str_dataset_id = $str_dataset;
        }
        $this->str_namespace = $str_namespace;

        if (!(self::$grpc_client instanceof \Google\Cloud\Datastore\V1\DatastoreClient))
        {
            self::$grpc_client = new DatastoreClient();
        }
    }

    /**
     * Get dataset and namespace ("partition") object
     *
     * Usually applied to a Key or RunQueryRequest
     *
     * @param object $obj_target
     * @return mixed
     */
    private function getPartitionId()
    {
        $obj = new PartitionId();
        $obj->setProjectId($this->str_dataset_id);
        if ($this->str_namespace !== null) {
            $obj->setNamespaceId($this->str_namespace);
        }
        return $obj;
    }

    /**
     * Execute a method against the Datastore client.
     *
     * @param $str_method
     * @param mixed[] $args
     * @return mixed
     * @throws ApiException
     * @throws Contention
     */
    private function execute($str_method, $args)
    {
        try {
            // Call gRPC client,
            //   prepend projectId as first parameter automatically.
            array_unshift($args, $this->str_dataset_id);
            $this->obj_last_response = call_user_func_array([self::$grpc_client, $str_method], $args);
        } catch (ApiException $obj_exception) {
            $this->obj_last_response = null;
            if (FALSE !== strpos($obj_exception->getMessage(), 'too much contention') || FALSE !== strpos($obj_exception->getMessage(), 'Concurrency')) {
                // LIVE: "too much contention on these datastore entities. please try again." LOCAL : "Concurrency exception."
                throw new Contention('Datastore contention', 409, $obj_exception);
            } else {
                throw $obj_exception;
            }
        }

        return $this->obj_last_response;
    }

    /**
     * Convert a RepeatedField to a standard array,
     * as it isn't compatible with the usual array functions.
     *
     * @param RepeatedField $rep
     * @return array
     */
    public function convertRepeatedField(RepeatedField $rep)
    {
        $arr = [];
        foreach ($rep as $v) {
            $arr[] = $v;
        }
        return $arr;
    }

    /**
     * Fetch 1-many Entities, using the Key parts provided
     *
     * @param array $arr_key_parts
     * @param $str_setter
     * @return \GDS\Entity[]|null
     */
    protected function fetchByKeyPart(array $arr_key_parts, $str_setter)
    {
        $keys = [];
        $partitionId = $this->getPartitionId();

        foreach($arr_key_parts as $mix_key_part) {
            $obj_key = new Key();
            $obj_key->setPartitionId($partitionId);

            $obj_kpe = new KeyPathElement();
            $obj_kpe->setKind($this->obj_schema->getKind());
            $obj_kpe->$str_setter($mix_key_part);

            $obj_key->setPath([$obj_kpe]);

            $keys[] = $obj_key;
        }

        $response = $this->execute('lookup', [$keys, ['readOptions' => $this->getReadOptions()]]);

        $results = $response->getFound();
        $arr_mapped_results = $this->createMapper()->mapFromResults($this->convertRepeatedField($results));

        $this->obj_schema = null; // Consume Schema

        return $arr_mapped_results;
    }

    /**
     * Put an array of Entities into the Datastore. Return any that need AutoIDs
     *
     * @todo Validate support for per-entity Schemas
     *
     * @param \GDS\Entity[] $arr_entities
     * @return \GDS\Entity[]
     */
    public function upsert(array $arr_entities)
    {
        $mutations = [];
        $arr_auto_id_required = [];

        foreach($arr_entities as $obj_gds_entity) {
            if(null === $obj_gds_entity->getKeyId() && null === $obj_gds_entity->getKeyName()) {
                $arr_auto_id_required[] = $obj_gds_entity; // maintain reference to the array of requested auto-ids
            }
            $obj_entity = new GRPC_Entity();
            $this->determineMapper($obj_gds_entity)->mapToGoogle($obj_gds_entity, $obj_entity);
            $mutations[] = (new Mutation())->setUpsert($obj_entity);
        }

        $options = [];
        if(null === $this->str_next_transaction) {
            $mode = CommitRequest_Mode::NON_TRANSACTIONAL;
        } else {
            $mode = CommitRequest_Mode::TRANSACTIONAL;
            $options['transaction'] = $this->getTransaction();
        }

        $this->execute('commit', [$mode, $mutations, $options]);

        return $arr_auto_id_required;
    }

    /**
     * Delete 1 or many entities, using their Keys
     *
     * Consumes Schema
     *
     * @todo Determine success. Not 100% how to do this from the response yet.
     *
     * @param array $arr_entities
     * @return bool
     */
    public function deleteMulti(array $arr_entities)
    {
        $obj_mapper = $this->createMapper();
        $partitionId = $this->getPartitionId();
        $mutations = [];

        foreach($arr_entities as $obj_gds_entity) {
            $obj_key = $obj_mapper->createGoogleKey($obj_gds_entity);
            $mutations[] = (new Mutation())->setDelete($obj_key);
        }

        $options = [];
        if(null === $this->str_next_transaction) {
            $mode = CommitRequest_Mode::NON_TRANSACTIONAL;
        } else {
            $mode = CommitRequest_Mode::TRANSACTIONAL;
            $options['transaction'] = $this->getTransaction();
        }

        $this->execute('commit', [$mode, $mutations, $options]);
        $this->obj_schema = null;
        return TRUE; // really?
    }

    /**
     * Fetch some Entities, based on the supplied GQL and, optionally, parameters
     *
     * @param string $str_gql
     * @param array|null $arr_params
     * @return \GDS\Entity[]|null
     * @throws \Exception
     */
    public function gql($str_gql, $arr_params = null)
    {
        $readOptions = $this->getReadOptions();

        $obj_gql_query = (new GqlQuery())->setAllowLiterals(true)->setQueryString($str_gql);

        if(null !== $arr_params) {
            $this->addParamsToQuery($obj_gql_query, $arr_params);
        }

        $obj_gql_response = $this->execute('runQuery', [
            $this->getPartitionId(), [
                'readOptions' => $readOptions,
                'gqlQuery' => $obj_gql_query
            ]
        ]);

        $results = $obj_gql_response->getBatch()->getEntityResults();
        $arr_mapped_results = $this->createMapper()->mapFromResults($this->convertRepeatedField($results));
        $this->obj_schema = null; // Consume Schema
        return $arr_mapped_results;
    }

    /**
     * Begin a transaction
     *
     * @todo Evaluate cross-request transactions [setCrossRequest]
     *
     * @param bool $bol_cross_group
     * @return string|null
     */
    public function beginTransaction($bol_cross_group = FALSE)
    {
        if($bol_cross_group) {
            // No longer supported??
            // $obj_request->setCrossGroup(TRUE);
        }
        $obj_response = $this->execute('beginTransaction', []);
        return $obj_response->getTransaction();
    }

    /**
     * Extract Auto Insert IDs from the last response
     *
     * @return array
     */
    protected function extractAutoIDs()
    {
        $arr_ids = [];
        foreach($this->obj_last_response->getMutationResults() as $obj_list) {
            $obj_key = $obj_list->getKey();
            if ($obj_key !== null) {
                $arr_key_path = $obj_key->getPath();
                $obj_path_end = end($arr_key_path);
                $arr_ids[] = $obj_path_end->getId();
            }
        }
        return $arr_ids;
    }

    /**
     * Get the transaction to apply to an object
     *
     * @return mixed
     */
    private function getTransaction()
    {
        $obj = $this->str_next_transaction;
        $this->str_next_transaction = null;
        return $obj;
    }

    /**
     * Get a ReadOptions object, containing transaction info.
     *
     * @return mixed
     */
    private function getReadOptions()
    {
        $obj = null;
        if(null !== $this->str_next_transaction) {
            $obj = new ReadOptions();
            $obj->setTransaction($this->getTransaction());
        }
        return $obj;
    }

    /**
     * Add Parameters to a GQL Query object
     *
     * @param GqlQuery $obj_query
     * @param array $arr_params
     */
    private function addParamsToQuery(GqlQuery $obj_query, array $arr_params)
    {
        if(count($arr_params) > 0) {
            $namedArgs = [];
            foreach ($arr_params as $str_name => $mix_value) {
                $obj_arg = new GqlQueryParameter();
                if ('startCursor' == $str_name) {
                    $obj_arg->setCursor($mix_value);
                } else {
                    $obj_val = new Value();
                    $this->configureObjectValueParamForQuery($obj_val, $mix_value);
                    $obj_arg->setValue($obj_val);
                }
                $namedArgs[$str_name] = $obj_arg;
            }
            $obj_query->setNamedBindings($namedArgs);
        }
    }

    /**
     * Configure a Value parameter, based on the supplied object-type value
     *
     * @todo Re-use one Mapper instance
     *
     * @param Value $obj_val
     * @param object $mix_value
     */
    protected function configureObjectValueParamForQuery($obj_val, $mix_value)
    {
        if($mix_value instanceof Entity) {
            $obj_key = $this->createMapper()->createGoogleKey($mix_value);
            $obj_val->setKeyValue($obj_key);
        } elseif ($mix_value instanceof \DateTimeInterface) {
            $timestamp = (new Timestamp())->setSeconds($mix_value->getTimestamp())->setNanos(1000 * $mix_value->format('u'));
            $obj_val->setTimestampValue($timestamp);
        } elseif (method_exists($mix_value, '__toString')) {
            $obj_val->setStringValue($mix_value->__toString());
        } else {
            throw new \InvalidArgumentException('Unexpected, non-string-able object parameter: ' . get_class($mix_value));
        }
    }

    /**
     * Get the end cursor from the last response
     */
    public function getEndCursor()
    {
        return $this->obj_last_response->getBatch()->getEndCursor();
    }

    /**
     * Create a mapper that's right for this Gateway
     *
     * @return \GDS\Mapper\GRPCv1
     */
    protected function createMapper()
    {
        return (new \GDS\Mapper\GRPCv1())->setSchema($this->obj_schema)->setPartitionId($this->getPartitionId());
    }
}