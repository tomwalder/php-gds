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

/**
 * GoogleAPIClient Datastore Gateway
 *
 * @author Tom Walder
 * @package GDS\Gateway
 */
class GoogleAPIClient extends \GDS\Gateway
{

    /**
     * @var \Google_Service_Datastore_Datasets_Resource|null
     */
    private $obj_datasets = null;

    /**
     * Create a new GDS service
     *
     * Optional namespace (for multi-tenant applications)
     *
     * @param \Google_Client $obj_client
     * @param $str_dataset_id
     * @param null $str_namespace
     */
    public function __construct(\Google_Client $obj_client, $str_dataset_id, $str_namespace = null)
    {
        $obj_service = new \Google_Service_Datastore($obj_client);
        $this->obj_datasets = $obj_service->datasets;
        $this->str_dataset_id = $str_dataset_id;
        $this->str_namespace = $str_namespace;
    }

    /**
     * Create a configured Google Client ready for Datastore use
     *
     * Consider using "fromJSON" helper method by Google
     *
     * @param $str_app_name
     * @param $str_service_account
     * @param $str_key_file
     * @return \Google_Client
     */
    public static function createGoogleClient($str_app_name, $str_service_account, $str_key_file)
    {
        $obj_client = new \Google_Client();
        $obj_client->setApplicationName($str_app_name);
        $str_key = file_get_contents($str_key_file);
        $obj_client->setAssertionCredentials(
            new \Google_Auth_AssertionCredentials(
                $str_service_account,
                [\Google_Service_Datastore::DATASTORE, \Google_Service_Datastore::USERINFO_EMAIL],
                $str_key
            )
        );
        // App Engine php55 runtime dev server problems...
        $obj_client->setClassConfig('Google_Http_Request', 'disable_gzip', TRUE);
        return $obj_client;
    }

    /**
     * Create a configured Google Client ready for Datastore use, using the JSON service file from Google Dev Console
     *
     * @param $str_json_file
     * @return \Google_Client
     */
    public static function createClientFromJson($str_json_file)
    {
        $obj_client = new \Google_Client();
        $obj_client->setAssertionCredentials($obj_client->loadServiceAccountJson(
            $str_json_file,
            [\Google_Service_Datastore::DATASTORE, \Google_Service_Datastore::USERINFO_EMAIL]
        ));
        // App Engine php55 runtime dev server problems...
        $obj_client->setClassConfig('Google_Http_Request', 'disable_gzip', TRUE);
        return $obj_client;
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
        $obj_mutation = new \Google_Service_Datastore_Mutation();
        /* @var $arr_auto_id_required \GDS\Entity[] */
        $arr_auto_id_required = [];
        $arr_mutation_auto_id = [];
        $arr_mutation_upsert = [];
        foreach($arr_entities as $obj_gds_entity) {
            $obj_google_entity = $this->determineMapper($obj_gds_entity)->mapToGoogle($obj_gds_entity);
            $this->applyNamespace($obj_google_entity->getKey());
            if(null === $obj_gds_entity->getKeyId() && null === $obj_gds_entity->getKeyName()) {
                $arr_mutation_auto_id[] = $obj_google_entity;
                $arr_auto_id_required[] = $obj_gds_entity; // maintain reference to the array of requested auto-ids
            } else {
                $arr_mutation_upsert[] = $obj_google_entity;
            }
        }
        if (!empty($arr_mutation_auto_id)) {
            $obj_mutation->setInsertAutoId($arr_mutation_auto_id);
        }
        if (!empty($arr_mutation_upsert)) {
            $obj_mutation->setUpsert($arr_mutation_upsert);
        }
        $this->commitMutation($obj_mutation);
        return $arr_auto_id_required;
    }

    /**
     * Extract Auto Insert IDs from the last response
     *
     * @return array
     */
    protected function extractAutoIDs()
    {
        $arr_ids = [];
        if(isset($this->obj_last_response['mutationResult']) && isset($this->obj_last_response['mutationResult']['insertAutoIdKeys'])) {
            foreach ($this->obj_last_response['mutationResult']['insertAutoIdKeys'] as $obj_key) {
                $arr_key_path = $obj_key->getPath();
                $arr_path_end = end($arr_key_path);
                $arr_ids[] = $arr_path_end['id'];
            }
        }
        return $arr_ids;
    }

    /**
     * Apply the current namespace to an object or array of objects
     *
     * @param $mix_target
     * @return mixed
     */
    private function applyNamespace($mix_target)
    {
        if(null !== $this->str_namespace) {
            $obj_partition = new \Google_Service_Datastore_PartitionId();
            $obj_partition->setNamespace($this->str_namespace);
            if(is_array($mix_target)) {
                foreach($mix_target as $obj_target) {
                    $obj_target->setPartitionId($obj_partition);
                }
            } else {
                $mix_target->setPartitionId($obj_partition);
            }
        }
        return $mix_target;
    }

    /**
     * If we are in a transaction, apply it to the request object
     *
     * @param $obj_request
     * @return mixed
     */
    private function applyTransaction($obj_request) {
        if(null !== $this->str_next_transaction) {
            $obj_read_options = new \Google_Service_Datastore_ReadOptions();
            $obj_read_options->setTransaction($this->str_next_transaction);
            $obj_request->setReadOptions($obj_read_options);
            $this->str_next_transaction = null;
        }
        return $obj_request;
    }

    /**
     * Apply a mutation to the Datastore (commit)
     *
     * @param \Google_Service_Datastore_Mutation $obj_mutation
     * @return \Google_Service_Datastore_CommitResponse
     * @throws Contention
     * @throws \Google_Service_Exception
     */
    private function commitMutation(\Google_Service_Datastore_Mutation $obj_mutation)
    {
        $obj_request = new \Google_Service_Datastore_CommitRequest();
        if(null === $this->str_next_transaction) {
            $obj_request->setMode('NON_TRANSACTIONAL');
        } else {
            $obj_request->setMode('TRANSACTIONAL');
            $obj_request->setTransaction($this->str_next_transaction);
            $this->str_next_transaction = NULL;
        }
        $obj_request->setMutation($obj_mutation);
        try {
            $this->obj_last_response = $this->obj_datasets->commit($this->str_dataset_id, $obj_request);
        } catch (\Google_Service_Exception $obj_exception) {
            $this->obj_last_response = NULL;
            if(409 == $obj_exception->getCode()) {
                throw new Contention('Datastore contention', 409, $obj_exception);
            } else {
                throw $obj_exception;
            }
        }
    }

    /**
     * Fetch 1-many Entities, using the Key parts provided
     *
     * @param array $arr_key_parts
     * @param $str_setter
     * @return mixed
     */
    protected function fetchByKeyPart(array $arr_key_parts, $str_setter)
    {
        $arr_keys = [];
        foreach($arr_key_parts as $str_key_part) {
            $obj_key = new \Google_Service_Datastore_Key();
            $obj_element = new \Google_Service_Datastore_KeyPathElement();
            $obj_element->setKind($this->obj_schema->getKind());
            $obj_element->$str_setter($str_key_part);
            $obj_key->setPath([$obj_element]);
            $arr_keys[] = $obj_key;
        }

        // Build, run & map the request
        return $this->fetchByKeys($arr_keys);
    }

    /**
     * Fetch entity data for an array of Google Datastore Keys
     *
     * Consumes the Schema
     * Consumes the Transaction
     *
     * @param \Google_Service_Datastore_Key[] $arr_keys
     * @return mixed
     */
    private function fetchByKeys(array $arr_keys)
    {
        $obj_request = $this->applyTransaction(new \Google_Service_Datastore_LookupRequest());
        $obj_request->setKeys($this->applyNamespace($arr_keys));
        $this->obj_last_response = $this->obj_datasets->lookup($this->str_dataset_id, $obj_request);
        $arr_results = $this->obj_last_response->getFound();
        $arr_mapped_results = $this->createMapper()->mapFromResults($arr_results);
        $this->obj_schema = null; // consume Schema
        return $arr_mapped_results;
    }

    /**
     * Delete one or more entities based on their Key
     *
     * Consumes Schema
     *
     * @todo determine success?
     *
     * @param array $arr_entities
     * @return bool
     */
    public function deleteMulti(array $arr_entities)
    {
        $obj_mutation = new \Google_Service_Datastore_Mutation();
        $arr_google_keys = $this->createMapper()->createKeys($arr_entities);
        foreach($arr_google_keys as $obj_key) {
            $this->applyNamespace($obj_key);
        }
        $obj_mutation->setDelete($arr_google_keys);
        $this->commitMutation($obj_mutation);
        $this->obj_schema = null;
        return TRUE; // really?
    }

    /**
     * Fetch some Entities, based on the supplied GQL and, optionally, parameters
     *
     * @param $str_gql
     * @param array $arr_params
     * @return Entity[]
     */
    public function gql($str_gql, $arr_params = null)
    {
        $obj_query = new \Google_Service_Datastore_GqlQuery();
        $obj_query->setAllowLiteral(TRUE);
        $obj_query->setQueryString($str_gql);
        if(null !== $arr_params) {
            $this->addParamsToQuery($obj_query, $arr_params);
        }
        $arr_mapped_results = $this->createMapper()->mapFromResults($this->executeQuery($obj_query));
        $this->obj_schema = null; // Consume Schema
        return $arr_mapped_results;
    }

    /**
     * Add Parameters to a GQL Query object
     *
     * @param \Google_Service_Datastore_GqlQuery $obj_query
     * @param array $arr_params
     */
    private function addParamsToQuery(\Google_Service_Datastore_GqlQuery $obj_query, array $arr_params)
    {
        if(count($arr_params) > 0) {
            $arr_args = [];
            foreach ($arr_params as $str_name => $mix_value) {
                $obj_arg = new \Google_Service_Datastore_GqlQueryArg();
                $obj_arg->setName($str_name);
                if ('startCursor' == $str_name) {
                    $obj_arg->setCursor($mix_value);
                } else {
                    $obj_arg->setValue($this->configureValueParamForQuery(new \Google_Service_Datastore_Value(), $mix_value));
                }
                $arr_args[] = $obj_arg;
            }
            $obj_query->setNameArgs($arr_args);
        }
    }

    /**
     * Configure a Value parameter, based on the supplied object-type value
     *
     * @todo Re-use one Mapper instance
     *
     * @param \Google_Service_Datastore_Value $obj_val
     * @param object $mix_value
     */
    protected function configureObjectValueParamForQuery($obj_val, $mix_value)
    {
        if($mix_value instanceof Entity) {
            $obj_val->setKeyValue($this->createMapper()->createKey($mix_value));
        } elseif ($mix_value instanceof \DateTime) {
            $obj_val->setDateTimeValue($mix_value->format(\DateTime::ATOM));
        } elseif (method_exists($mix_value, '__toString')) {
            $obj_val->setStringValue($mix_value->__toString());
        } else {
            throw new \InvalidArgumentException('Unexpected, non-string-able object parameter: ' . get_class($mix_value));
        }
    }

    /**
     * Execute the given query and return the results.
     *
     * @param \Google_Collection $obj_query
     * @return array
     */
    private function executeQuery(\Google_Collection $obj_query)
    {
        $obj_request = $this->applyTransaction(
            $this->applyNamespace(
                new \Google_Service_Datastore_RunQueryRequest()
            )
        );
        if ($obj_query instanceof \Google_Service_Datastore_GqlQuery) {
            $obj_request->setGqlQuery($obj_query);
        } else {
            $obj_request->setQuery($obj_query);
        }
        $this->obj_last_response = $this->obj_datasets->runQuery($this->str_dataset_id, $obj_request);
        if (isset($this->obj_last_response['batch']['entityResults'])) {
            return $this->obj_last_response['batch']['entityResults'];
        }
        return [];
    }

    /**
     * Get the end Cursor for the last response
     *
     * @return mixed
     */
    public function getEndCursor()
    {
        return $this->obj_last_response['batch']['endCursor'];
    }

    /**
     * Create a mapper that's right for this Gateway
     *
     * @return \GDS\Mapper\GoogleAPIClient
     */
    protected function createMapper()
    {
        return (new \GDS\Mapper\GoogleAPIClient())->setSchema($this->obj_schema);
    }

    /**
     * Begin a transaction and return it's reference id
     *
     * @param bool $bol_cross_group
     * @return string
     * @throws \Exception
     */
    public function beginTransaction($bol_cross_group = FALSE)
    {
        if($bol_cross_group) {
            throw new \Exception("Cross group transactions not supported over JSON API");
        }
        $obj_request = new \Google_Service_Datastore_BeginTransactionRequest();
        /** @var \Google_Service_Datastore_BeginTransactionResponse $obj_response */
        $obj_response = $this->obj_datasets->beginTransaction($this->str_dataset_id, $obj_request);
        return $obj_response->getTransaction();
    }
}