<?php namespace GDS\Gateway;

use GDS\Mapper;

use Google\Auth\ApplicationDefaultCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

/**
 * Gateway, implementing the Datastore API v1 over REST
 *
 * https://cloud.google.com/datastore/reference/rest/
 *
 * @package GDS\Gateway
 */
class RESTv1 extends \GDS\Gateway
{

    const BASE_URL = 'https://datastore.googleapis.com';

    //    allocateIds	POST /v1/projects/{projectId}:allocateIds
    //    commit	    POST /v1/projects/{projectId}:commit
    //    lookup	    POST /v1/projects/{projectId}:lookup
    //    rollback	    POST /v1/projects/{projectId}:rollback
    //    runQuery	    POST /v1/projects/{projectId}:runQuery

    /**
     * @var \GuzzleHttp\Client
     */
    private $obj_http_client = null;

    /**
     * Create the auth middleware and set up the HTTP Client
     *
     * @param $str_project_id
     */
    public function __construct($str_project_id)
    {
        $this->str_dataset_id = $str_project_id;

        // Middleware
        $obj_stack = HandlerStack::create();
        $obj_stack->push(ApplicationDefaultCredentials::getMiddleware(['https://www.googleapis.com/auth/datastore']));

        // Create the HTTP client
        $this->obj_http_client = new Client([
            'handler' => $obj_stack,
            'base_url' => self::BASE_URL,
            'auth' => 'google_auth'  // authorize all requests
        ]);

    }

    /**
     * Configure a Value parameter, based on the supplied object-type value
     *
     * @param object $obj_val
     * @param object $mix_value
     */
    protected function configureObjectValueParamForQuery($obj_val, $mix_value)
    {
        // TODO: Implement configureObjectValueParamForQuery() method.
    }

    /**
     * Put an array of Entities into the Datastore. Return any that need AutoIDs
     *
     * @param \GDS\Entity[] $arr_entities
     * @return \GDS\Entity[]
     */
    protected function upsert(array $arr_entities)
    {
        // TODO: Implement upsert() method.
    }

    /**
     * Extract Auto Insert IDs from the last response
     *
     * @return array
     */
    protected function extractAutoIDs()
    {
        // TODO: Implement extractAutoIDs() method.
    }

    /**
     * Fetch 1-many Entities, using the Key parts provided
     *
     * Consumes Schema
     *
     * @param array $arr_key_parts
     * @param $str_setter
     * @return mixed
     */
    protected function fetchByKeyPart(array $arr_key_parts, $str_setter)
    {
        // TODO: Implement fetchByKeyPart() method.
    }

    /**
     * Delete 1-many entities
     *
     * @param array $arr_entities
     * @return mixed
     */
    public function deleteMulti(array $arr_entities)
    {
        // TODO: Implement deleteMulti() method.
    }

    /**
     * Fetch some Entities, based on the supplied GQL and, optionally, parameters
     *
     * @param string $str_gql
     * @param null|array $arr_params
     * @return mixed
     */
    public function gql($str_gql, $arr_params = null)
    {
        // TODO: Implement gql() method.
    }

    /**
     * Get the end cursor from the last response
     *
     * @return mixed
     */
    public function getEndCursor()
    {
        // TODO: Implement getEndCursor() method.
    }

    /**
     * Create a mapper that's right for this Gateway
     *
     * @return Mapper
     */
    protected function createMapper()
    {
        // TODO: Implement createMapper() method.
    }

    /**
     * Start a transaction
     *
     * POST /v1/projects/{projectId}:beginTransaction
     *
     * @param bool $bol_cross_group
     * @return null
     * @throws \Exception
     */
    public function beginTransaction($bol_cross_group = FALSE)
    {
        if($bol_cross_group) {
            throw new \Exception("Cross group transactions not supported over REST API v1");
        }
        $obj_response = $this->obj_http_client->post($this->actionUrl('beginTransaction'));
        $obj_response_data = json_decode((string) $obj_response->getBody());
        if($obj_response_data && isset($obj_response_data->transaction)) {
            return $obj_response_data->transaction;
        }
        return null;
    }

    /**
     * Build a URL for a Datastore action
     *
     * @param $str_action
     * @return string
     */
    private function actionUrl($str_action)
    {
        return self::BASE_URL . '/v1/projects/' . $this->str_dataset_id . ':' . $str_action;
    }

}