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
use GDS\Gateway;
use Google\ApiCore\ApiException;
use Google\Cloud\Datastore\V1\LookupResponse;
use Google\Protobuf\Timestamp;
use Google\Protobuf\Internal\RepeatedField;
use Google\Cloud\Datastore\V1\DatastoreClient;
use Google\Cloud\Datastore\V1\Entity as GRPC_Entity;
use Google\Cloud\Datastore\V1\Key;
use Google\Cloud\Datastore\V1\Key\PathElement as KeyPathElement;
use Google\Cloud\Datastore\V1\PartitionId;
use Google\Cloud\Datastore\V1\CommitRequest\Mode;
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
     * Cloud Datastore (gRPC & REST) Client
     */
    protected static $obj_datastore_client;

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
            $str_dataset = Gateway::determineProjectId();
        }
        $this->str_dataset_id = $str_dataset;
        $this->str_namespace = $str_namespace;

        // Build the Datastore client
        if (!(self::$obj_datastore_client instanceof DatastoreClient)) {
            $arr_options = [];
            if (!extension_loaded('grpc')) {
                $arr_options = ['transport' => 'grpc-fallback']; // This is Protocol buffers over HTTP 1.1
            }
            self::$obj_datastore_client = new DatastoreClient($arr_options);
        }
    }

    /**
     * Get dataset and namespace ("partition") object
     *
     * Usually applied to a Key or RunQueryRequest
     *
     * @return PartitionId
     */
    private function createPartitionId()
    {
        $obj_partition_id = (new PartitionId())
            ->setProjectId($this->str_dataset_id);
        if ($this->str_namespace !== null) {
            $obj_partition_id->setNamespaceId($this->str_namespace);
        }
        return $obj_partition_id;
    }

    /**
     * Execute a method against the Datastore client.
     *
     * @param string $str_method
     * @param mixed[] $args
     * @return mixed
     * @throws \Exception
     */
    private function execute($str_method, $args)
    {
        try {
            // Call gRPC client,
            //   prepend projectId as first parameter automatically.
            array_unshift($args, $this->str_dataset_id);
            $this->obj_last_response = call_user_func_array([self::$obj_datastore_client, $str_method], $args);
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
     * @param RepeatedField $obj_repeats
     * @return array
     */
    public function convertRepeatedFieldToArray(RepeatedField $obj_repeats)
    {
        $arr_values = [];
        foreach ($obj_repeats as $obj_value) {
            $arr_values[] = $obj_value;
        }
        return $arr_values;
    }

    /**
     * Fetch 1-many Entities, using the Key parts provided
     *
     * @param array $arr_key_parts
     * @param string $str_setter
     * @return \GDS\Entity[]|null
     * @throws \Exception
     */
    protected function fetchByKeyPart(array $arr_key_parts, $str_setter)
    {
        $keys = [];
        $partitionId = $this->createPartitionId();

        foreach($arr_key_parts as $mix_key_part) {
            $obj_key = new Key();
            $obj_key->setPartitionId($partitionId);

            $obj_kpe = new KeyPathElement();
            $obj_kpe->setKind($this->obj_schema->getKind());
            $obj_kpe->$str_setter($mix_key_part);

            $obj_key->setPath([$obj_kpe]);

            $keys[] = $obj_key;
        }

        /** @var LookupResponse $obj_response */
        $obj_response = $this->execute('lookup', [$keys, ['readOptions' => $this->getReadOptions()]]);
        $arr_mapped_results = $this->createMapper()
            ->mapFromResults(
                $this->convertRepeatedFieldToArray(
                    $obj_response->getFound()
                )
            );

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
     * @throws \Exception
     */
    public function upsert(array $arr_entities)
    {
        $arr_mutations = [];
        $arr_auto_id_required = [];

        foreach($arr_entities as $obj_gds_entity) {
            if(null === $obj_gds_entity->getKeyId() && null === $obj_gds_entity->getKeyName()) {
                $arr_auto_id_required[] = $obj_gds_entity; // maintain reference to the array of requested auto-ids
            }
            $obj_entity = new GRPC_Entity();
            $this->determineMapper($obj_gds_entity)->mapToGoogle($obj_gds_entity, $obj_entity);
            $arr_mutations[] = (new Mutation())->setUpsert($obj_entity);
        }

        $arr_options = [];
        if(null === $this->str_next_transaction) {
            $int_mode = Mode::NON_TRANSACTIONAL;
        } else {
            $int_mode = Mode::TRANSACTIONAL;
            $arr_options['transaction'] = $this->getTransaction();
        }

        $this->execute('commit', [$int_mode, $arr_mutations, $arr_options]);

        return $arr_auto_id_required;
    }

    /**
     * Delete 1 or many entities, using their Keys
     *
     * Consumes Schema
     *
     * @todo Determine success. Not 100% how to do this from the response yet.
     *
     * @todo Tests...
     *
     * @param array $arr_entities
     * @return bool
     * @throws \Exception
     */
    public function deleteMulti(array $arr_entities)
    {
        $obj_mapper = $this->createMapper();
        // $partitionId = $this->createPartitionId();
        $arr_mutations = [];

        foreach($arr_entities as $obj_gds_entity) {
            $obj_key = $obj_mapper->createGoogleKey($obj_gds_entity);
            $arr_mutations[] = (new Mutation())->setDelete($obj_key);
        }

        // @todo withTransaction???
        $options = [];
        if(null === $this->str_next_transaction) {
            $int_mode = Mode::NON_TRANSACTIONAL;
        } else {
            $int_mode = Mode::TRANSACTIONAL;
            $options['transaction'] = $this->getTransaction();
        }

        $this->execute('commit', [$int_mode, $arr_mutations, $options]);
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
        $obj_read_options = $this->getReadOptions();

        $obj_gql_query = (new GqlQuery())
            ->setAllowLiterals(true)
            ->setQueryString($str_gql);

        if(null !== $arr_params) {
            $this->addParamsToQuery($obj_gql_query, $arr_params);
        }

        $obj_gql_response = $this->execute(
            'runQuery',
            [
                $this->createPartitionId(),
                [
                    'readOptions' => $obj_read_options,
                    'gqlQuery' => $obj_gql_query
                ]
            ]
        );

        $obj_results = $obj_gql_response->getBatch()->getEntityResults();
        $arr_mapped_results = $this->createMapper()
            ->mapFromResults(
                $this->convertRepeatedFieldToArray($obj_results)
            );
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
     * @throws \Exception
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
            /** @var \Google\Cloud\Datastore\V1\MutationResult $obj_list */
            $obj_key = $obj_list->getKey();
            if ($obj_key !== null) {
                /** @var \Google\Protobuf\Internal\RepeatedField $obj_key_path */
                $obj_key_path = $obj_key->getPath();
                $int_last_index = count($obj_key_path) - 1;
                $obj_path_end = $obj_key_path[$int_last_index];
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
     * @return null|ReadOptions
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
                    $this->configureValueParamForQuery($obj_val, $mix_value);
                    $obj_arg->setValue($obj_val);
                }
                $namedArgs[$str_name] = $obj_arg;
            }
            $obj_query->setNamedBindings($namedArgs);
        }
    }

    /**
     * Part of our "add parameters to query" sequence.
     *
     * @param Value $obj_val
     * @param mixed $mix_value
     * @return Value
     */
    protected function configureValueParamForQuery($obj_val, $mix_value)
    {
        $str_type = gettype($mix_value);
        switch($str_type) {
            case 'boolean':
                $obj_val->setBooleanValue($mix_value);
                break;

            case 'integer':
                $obj_val->setIntegerValue($mix_value);
                break;

            case 'double':
                $obj_val->setDoubleValue($mix_value);
                break;

            case 'string':
                $obj_val->setStringValue($mix_value);
                break;

            case 'array':
                throw new \InvalidArgumentException('Unexpected array parameter');

            case 'object':
                $this->configureObjectValueParamForQuery($obj_val, $mix_value);
                break;

            case 'NULL':
                $obj_val->setNullValue(null);
                break;

            case 'resource':
            case 'unknown type':
            default:
                throw new \InvalidArgumentException('Unsupported parameter type: ' . $str_type);
        }
        return $obj_val;
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
            $obj_timestamp = (new Timestamp())
                ->setSeconds($mix_value->getTimestamp())
                ->setNanos(1000 * $mix_value->format('u'));
            $obj_val->setTimestampValue($obj_timestamp);
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
        return (new \GDS\Mapper\GRPCv1())
            ->setSchema($this->obj_schema)
            ->setPartitionId(
                $this->createPartitionId()
            );
    }
}