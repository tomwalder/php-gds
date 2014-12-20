<?php
/**
 * GDS Gateway
 *
 * Persists and retrieves Entities to/from GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
namespace GDS;

/**
 * Google Datastore Gateway
 *
 * @package GDS
 */
class Gateway
{

    /**
     * @var \Google_Service_Datastore_Datasets_Resource|null
     */
    private $obj_datasets = NULL;

    /**
     * @var string
     */
    private $str_dataset_id = NULL;

    /**
     * The last response - sually a Commit or Query response
     *
     * @var \Google_Model
     */
    private $obj_last_response = NULL;

    /**
     * Create a new GDS service
     *
     * @param \Google_Client $obj_client
     * @param $str_dataset_id
     */
    public function __construct(\Google_Client $obj_client, $str_dataset_id)
    {
        $obj_service = new \Google_Service_Datastore($obj_client);
        $this->obj_datasets = $obj_service->datasets;
        $this->str_dataset_id = $str_dataset_id;
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
     * Put a single Entity into the Datastore
     *
     * @param \Google_Service_Datastore_Entity $obj_entity
     * @return bool
     */
    public function put(\Google_Service_Datastore_Entity $obj_entity)
    {
        return $this->putMulti([$obj_entity]);
    }

    /**
     * Put an array of Entities into the Datastore
     *
     * @todo Transactions
     *
     * @param \Google_Service_Datastore_Entity[] $arr_entities
     * @return bool
     */
    public function putMulti(array $arr_entities)
    {
        $obj_mutation = new \Google_Service_Datastore_Mutation();
        $arr_auto_id = [];
        $arr_has_key = [];
        foreach($arr_entities as $obj_entity) {
            /** @var \Google_Service_Datastore_KeyPathElement $obj_path */
            $obj_path = $obj_entity->getKey()->getPath()[0];
            if($obj_path->getId() || $obj_path->getName()) {
                $arr_has_key[] = $obj_entity;
            } else {
                $arr_auto_id[] = $obj_entity;
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
     * Apply a mutation to the Datastore (commit)
     *
     * @param \Google_Service_Datastore_Mutation $obj_mutation
     * @return \Google_Service_Datastore_CommitResponse
     */
    private function commitMutation(\Google_Service_Datastore_Mutation $obj_mutation)
    {
        $obj_request = new \Google_Service_Datastore_CommitRequest();
        $obj_request->setMode('NON_TRANSACTIONAL');
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
     * Fetch entity data for an array of Keys
     *
     * @param $arr_keys
     * @return mixed
     */
    private function fetchByKeys($arr_keys)
    {
        $obj_request = new \Google_Service_Datastore_LookupRequest();
        $obj_request->setKeys($arr_keys);
        $this->obj_last_response = $this->obj_datasets->lookup($this->str_dataset_id, $obj_request);
        return $this->obj_last_response->getFound();
    }

    /**
     * Fetch entity data based on GQL
     *
     * @param $str_gql
     * @return Model[]
     */
    public function gql($str_gql)
    {
        $obj_query = new \Google_Service_Datastore_GqlQuery();
        $obj_query->setAllowLiteral(TRUE);
        $obj_query->setQueryString($str_gql);
        return $this->executeQuery($obj_query);
    }

    /**
     * Execute the given query and return the results.
     *
     * @param \Google_Collection $obj_query
     * @return array
     */
    private function executeQuery(\Google_Collection $obj_query)
    {
        $obj_request = new \Google_Service_Datastore_RunQueryRequest();
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
        $obj_mutation = new \Google_Service_Datastore_Mutation();
        $obj_mutation->setDelete([$obj_key]);
        $this->obj_last_response = $this->commitMutation($obj_mutation);
        return(1 == $this->obj_last_response->getMutationResult()['indexUpdates']);
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
        return(count($arr_keys) == $this->obj_last_response->getMutationResult()['indexUpdates']);
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

}