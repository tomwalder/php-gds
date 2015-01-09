<?php
/**
 * Copyright 2014 Tom Walder
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
namespace GDS;

/**
 * Google Datastore Gateway
 *
 * Persists and retrieves Entities to/from GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 */
class Gateway
{

    /**
     * @var \Google_Service_Datastore_Datasets_Resource|null
     */
    private $obj_datasets = NULL;

    /**
     * The dataset ID
     *
     * @var string
     */
    private $str_dataset_id = NULL;

    /**
     * Optional namespace (for multi-tenant applications)
     *
     * @var string|null
     */
    private $str_namespace = NULL;

    /**
     * The last response - usually a Commit or Query response
     *
     * @var \Google_Model
     */
    private $obj_last_response = NULL;

    /**
     * The transaction ID to use on the next commit
     *
     * @var null|string
     */
    private $str_next_transaction = NULL;

    /**
     * Create a new GDS service
     *
     * Optional namespace (for multi-tenant applications)
     *
     * @param \Google_Client $obj_client
     * @param $str_dataset_id
     * @param null $str_namespace
     */
    public function __construct(\Google_Client $obj_client, $str_dataset_id, $str_namespace = NULL)
    {
        $obj_service = new \Google_Service_Datastore($obj_client);
        $this->obj_datasets = $obj_service->datasets;
        $this->str_dataset_id = $str_dataset_id;
        $this->str_namespace = $str_namespace;
    }

    /**
     * Create a configured Google Client ready for Datastore use
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
        return $obj_client;
    }

    /**
     * Set the transaction ID to be used next (once)
     *
     * @param $str_transaction_id
     * @return $this
     */
    public function withTransaction($str_transaction_id)
    {
        $this->str_next_transaction = $str_transaction_id;
        return $this;
    }

    /**
     * Put a single Entity into the Datastore
     *
     * @param \Google_Service_Datastore_Entity $obj_google_entity
     * @return bool
     */
    public function put(\Google_Service_Datastore_Entity $obj_google_entity)
    {
        return $this->putMulti([$obj_google_entity]);
    }

    /**
     * Put an array of Entities into the Datastore
     *
     * @param \Google_Service_Datastore_Entity[] $arr_google_entities
     * @return bool
     */
    public function putMulti(array $arr_google_entities)
    {
        $obj_mutation = new \Google_Service_Datastore_Mutation();
        $arr_auto_id = [];
        $arr_has_key = [];
        foreach ($arr_google_entities as $obj_google_entity) {
            $obj_key = $this->applyNamespace($obj_google_entity->getKey());
            /** @var \Google_Service_Datastore_KeyPathElement $obj_path_end */
            $obj_path_end = end($obj_key->getPath());
            if ($obj_path_end->getId() || $obj_path_end->getName()) {
                $arr_has_key[] = $obj_google_entity;
            } else {
                $arr_auto_id[] = $obj_google_entity;
            }
        }
        if (!empty($arr_auto_id)) {
            $obj_mutation->setInsertAutoId($arr_auto_id);
        }
        if (!empty($arr_has_key)) {
            $obj_mutation->setUpsert($arr_has_key);
        }
        $this->commitMutation($obj_mutation);
        return TRUE;
    }

    /**
     * Apply the current namespace to an object or array of objects
     *
     * @param $mix_target
     * @return mixed
     */
    private function applyNamespace($mix_target)
    {
        if(NULL !== $this->str_namespace) {
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
     * Apply a mutation to the Datastore (commit)
     *
     * @param \Google_Service_Datastore_Mutation $obj_mutation
     * @return \Google_Service_Datastore_CommitResponse
     */
    private function commitMutation(\Google_Service_Datastore_Mutation $obj_mutation)
    {
        $obj_request = new \Google_Service_Datastore_CommitRequest();
        if(NULL === $this->str_next_transaction) {
            $obj_request->setMode('NON_TRANSACTIONAL');
        } else {
            $obj_request->setMode('TRANSACTIONAL');
            $obj_request->setTransaction($this->str_next_transaction);
            $this->str_next_transaction = NULL;
        }
        $obj_request->setMutation($obj_mutation);
        $this->obj_last_response = $this->obj_datasets->commit($this->str_dataset_id, $obj_request);
        return $this->obj_last_response;
    }

    /**
     * Fetch entity data by Key ID
     *
     * @param $str_kind
     * @param $str_key_id
     * @return array
     */
    public function fetchById($str_kind, $str_key_id)
    {
        $obj_path = new \Google_Service_Datastore_KeyPathElement();
        $obj_path->setKind($str_kind);
        $obj_path->setId($str_key_id);
        $obj_key = new \Google_Service_Datastore_Key();
        $obj_key->setPath([$obj_path]);
        return $this->fetchByKeys([$obj_key]);
    }

    /**
     * Fetch entity data by Key Name
     *
     * @param $str_kind
     * @param $str_key_name
     * @return mixed
     */
    public function fetchByName($str_kind, $str_key_name)
    {
        $obj_path = new \Google_Service_Datastore_KeyPathElement();
        $obj_path->setKind($str_kind);
        $obj_path->setName($str_key_name);
        $obj_key = new \Google_Service_Datastore_Key();
        $obj_key->setPath([$obj_path]);
        return $this->fetchByKeys([$obj_key]);
    }

    /**
     * Fetch entity data for an array of Google Datastore Keys
     *
     * @param \Google_Service_Datastore_Key[] $arr_keys
     * @return mixed
     */
    private function fetchByKeys(array $arr_keys)
    {
        $obj_request = new \Google_Service_Datastore_LookupRequest();
        $obj_request->setKeys($this->applyNamespace($arr_keys));
        $this->obj_last_response = $this->obj_datasets->lookup($this->str_dataset_id, $obj_request);
        return $this->obj_last_response->getFound();
    }

    /**
     * Fetch entity data based on GQL (and optional parameters)
     *
     * @param $str_gql
     * @param array $arr_params
     * @return Entity[]
     */
    public function gql($str_gql, $arr_params = NULL)
    {
        $obj_query = new \Google_Service_Datastore_GqlQuery();
        $obj_query->setAllowLiteral(TRUE);
        $obj_query->setQueryString($str_gql);
        if(NULL !== $arr_params) {
            $this->addParamsToQuery($obj_query, $arr_params);
        }
        return $this->executeQuery($obj_query);
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
                    $obj_val = new \Google_Service_Datastore_Value();
                    if($mix_value instanceof \Google_Service_Datastore_Key) {
                        $obj_val->setKeyValue($mix_value);
                    } elseif (is_int($mix_value)) {
                        $obj_val->setIntegerValue($mix_value);
                    } else {
                        $obj_val->setStringValue($mix_value);
                    }
                    $obj_arg->setValue($obj_val);
                }
                $arr_args[] = $obj_arg;
            }
            $obj_query->setNameArgs($arr_args);
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
        $obj_request = $this->applyNamespace(new \Google_Service_Datastore_RunQueryRequest());
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
     * Delete an Entity
     *
     * @param \Google_Service_Datastore_Key $obj_key
     * @return bool
     */
    public function delete(\Google_Service_Datastore_Key $obj_key)
    {
        return $this->deleteMulti([$obj_key]);
    }

    /**
     * Delete one or more entities based on their Key
     *
     * @param array $arr_keys
     * @return bool
     */
    public function deleteMulti(array $arr_keys)
    {
        $obj_mutation = new \Google_Service_Datastore_Mutation();
        $obj_mutation->setDelete($arr_keys);
        $this->obj_last_response = $this->commitMutation($obj_mutation);
        return TRUE;
    }

    /**
     * Begin a transaction and return it's reference id
     *
     * @return string
     */
    public function beginTransaction()
    {
        $obj_request = new \Google_Service_Datastore_BeginTransactionRequest();
        /** @var \Google_Service_Datastore_BeginTransactionResponse $obj_response */
        $obj_response = $this->obj_datasets->beginTransaction($this->str_dataset_id, $obj_request);
        return $obj_response->getTransaction();
    }

    /**
     * Retrieve the last response object
     *
     * @return \Google_Model
     */
    public function getLastResponse()
    {
        return $this->obj_last_response;
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

}