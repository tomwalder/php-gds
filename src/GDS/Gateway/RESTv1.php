<?php namespace GDS\Gateway;

use GDS\Entity;
use Google\Auth\ApplicationDefaultCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
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

    /**
     * Modes
     */
    const MODE_TRANSACTIONAL = 'TRANSACTIONAL';
    const MODE_NON_TRANSACTIONAL = 'NON_TRANSACTIONAL';
    const MODE_UNSPECIFIED = 'UNSPECIFIED';

    /**
     * Client config keys.
     */
    const CONFIG_CLIENT_BASE_URL = 'base_url';

    /**
     * Default Base URL to use.
     */
    const DEFAULT_BASE_URL = 'https://datastore.googleapis.com';

    /**
     * @var ClientInterface
     */
    private $obj_http_client = null;

    /**
     * Create the auth middleware and set up the HTTP Client
     *
     * @param $str_project_id
     * @param null $str_namespace
     */
    public function __construct($str_project_id, $str_namespace = null)
    {
        $this->str_dataset_id = $str_project_id;
        $this->str_namespace = $str_namespace;
    }

    /**
     * Use a pre-configured HTTP Client
     *
     * @param ClientInterface $obj_client
     * @return $this
     */
    public function setHttpClient(ClientInterface $obj_client)
    {
        $this->obj_http_client = $obj_client;
        return $this;
    }

    /**
     * Get the current HTTP Client in use
     *
     * @return ClientInterface
     */
    public function getHttpClient()
    {
        return $this->obj_http_client;
    }

    /**
     * Lazily initialise the HTTP Client when needed. Once.
     *
     * @return ClientInterface
     */
    protected function httpClient()
    {
        if (null === $this->obj_http_client) {
            $this->obj_http_client = $this->initHttpClient();
        }
        return $this->obj_http_client;
    }

    /**
     * Configure HTTP Client
     *
     * @return ClientInterface
     */
    protected function initHttpClient()
    {

        // Middleware
        $obj_stack = HandlerStack::create();
        $obj_stack->push(ApplicationDefaultCredentials::getMiddleware(['https://www.googleapis.com/auth/datastore']));

        $str_base_url = self::DEFAULT_BASE_URL;

        if (getenv("DATASTORE_EMULATOR_HOST") !== FALSE) {
            $str_base_url = getenv("DATASTORE_EMULATOR_HOST");
        }

        // Create the HTTP client
        return new Client([
            'handler' => $obj_stack,
            'base_url' => $str_base_url,
            'auth' => 'google_auth'  // authorize all requests
        ]);
    }

    /**
     * Put an array of Entities into the Datastore. Return any that need AutoIDs
     *
     * @param \GDS\Entity[] $arr_entities
     * @return \GDS\Entity[]
     */
    protected function upsert(array $arr_entities)
    {
        /* @var $arr_auto_id_required \GDS\Entity[] */
        $arr_auto_id_required = [];

        // Keep arrays of mutation types, so we can be more comfortable later with the ID mapping sequence
        $arr_inserts = [];
        $arr_upserts = [];

        foreach($arr_entities as $obj_gds_entity) {

            // Build a REST object, apply current partition
            $obj_rest_entity = $this->createMapper()->mapToGoogle($obj_gds_entity);
            $this->applyPartition($obj_rest_entity->key);

            if(null === $obj_gds_entity->getKeyId() && null === $obj_gds_entity->getKeyName()) {
                $arr_inserts[] = (object)['insert' => $obj_rest_entity];
                $arr_auto_id_required[] = $obj_gds_entity; // maintain reference to the array of requested auto-ids
            } else {
                $arr_upserts[] = (object)['upsert' => $obj_rest_entity];
            }
        }

        // Build the base request, add the prepared mutations
        $obj_request = $this->buildCommitRequest();
        $obj_request->mutations = array_merge($arr_inserts, $arr_upserts);

        // Run
        $this->executePostRequest('commit', $obj_request);

        return $arr_auto_id_required;
    }

    /**
     * Execute a POST request against the API
     *
     * @param $str_action
     * @param null $obj_request_body
     */
    private function executePostRequest($str_action, $obj_request_body = null)
    {
        $arr_options = [];
        if(null !== $obj_request_body) {
            $arr_options['json'] = $obj_request_body;
        }
        $obj_response = $this->httpClient()->post($this->actionUrl($str_action), $arr_options);
        $this->obj_last_response = json_decode((string)$obj_response->getBody());
    }

    /**
     * Build a basic commit request (used by upsert, delete)
     *
     * @return object
     */
    private function buildCommitRequest()
    {
        $obj_request = (object)['mutations' => []];

        // Transaction at root level, so do not use applyTransaction()
        if(null !== $this->str_next_transaction) {
            $obj_request->transaction = $this->str_next_transaction;
            $obj_request->mode = self::MODE_TRANSACTIONAL;
            $this->str_next_transaction = null;
        } else {
            $obj_request->mode = self::MODE_NON_TRANSACTIONAL;
        }
        return $obj_request;
    }

    /**
     * Extract Auto Insert IDs from the last response
     *
     * https://cloud.google.com/datastore/reference/rest/v1/projects/commit#MutationResult
     *
     * @return array
     */
    protected function extractAutoIDs()
    {
        $arr_ids = [];
        if(isset($this->obj_last_response->mutationResults) && is_array($this->obj_last_response->mutationResults)) {
            foreach ($this->obj_last_response->mutationResults as $obj_mutation_result) {
                if(isset($obj_mutation_result->key)) {
                    $obj_path_end = end($obj_mutation_result->key->path);
                    $arr_ids[] = $obj_path_end->id;
                }
            }
        }
        return $arr_ids;
    }

    /**
     * Delete 1-many entities
     *
     * @param array $arr_entities
     * @return mixed
     */
    public function deleteMulti(array $arr_entities)
    {

        // Build the base request
        $obj_request = $this->buildCommitRequest();

        // Create JSON keys for each delete mutation
        foreach($arr_entities as $obj_gds_entity) {
            $obj_rest_key = (object)['path' => $this->createMapper()->buildKeyPath($obj_gds_entity)];
            $this->applyPartition($obj_rest_key);
            $obj_request->mutations[] = (object)['delete' => $obj_rest_key];
        }

        // Run
        $this->executePostRequest('commit', $obj_request);

        return true; // Still not sure about this...
    }

    /**
     * Fetch some Entities, based on the supplied GQL and, optionally, parameters
     *
     * POST /v1/projects/{projectId}:runQuery
     *
     * @todo Look into using this to avoid unwanted further "fetch" at the end of paginated result: $this->obj_last_response->batch->moreResults == NO_MORE_RESULTS
     *
     * @param string $str_gql
     * @param null|array $arr_params
     * @return mixed
     */
    public function gql($str_gql, $arr_params = null)
    {

        // Build the query
        $obj_request = (object)[
            'gqlQuery' => (object)[
                'allowLiterals' => true,
                'queryString' => $str_gql
            ]
        ];
        $this->applyPartition($obj_request);
        $this->applyTransaction($obj_request);
        if(is_array($arr_params)) {
            $this->addParamsToQuery($obj_request->gqlQuery, $arr_params);
        }

        // Run
        $this->executePostRequest('runQuery', $obj_request);

        // Extract results
        $arr_mapped_results = [];
        if(isset($this->obj_last_response->batch->entityResults) && is_array($this->obj_last_response->batch->entityResults)) {
            $arr_mapped_results = $this->createMapper()->mapFromResults($this->obj_last_response->batch->entityResults);
        }
        $this->obj_schema = null; // Consume Schema
        return $arr_mapped_results;

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

        // Build the query
        $obj_request = (object)[
            'keys' => []
        ];
        $this->applyTransaction($obj_request);

        // Add keys
        foreach($arr_key_parts as $str_key_part) {
            $obj_element = (object)['kind' => $this->obj_schema->getKind()];
            if('setId' === $str_setter) {
                $obj_element->id = $str_key_part;
            } elseif ('setName' === $str_setter) {
                $obj_element->name = $str_key_part;
            }
            $obj_key = (object)['path' => [$obj_element]];
            $this->applyPartition($obj_key);
            $obj_request->keys[] = $obj_key;
        }

        // Run
        $this->executePostRequest('lookup', $obj_request);

        // Extract results
        $arr_mapped_results = [];
        if(isset($this->obj_last_response->found) && is_array($this->obj_last_response->found)) {
            $arr_mapped_results = $this->createMapper()->mapFromResults($this->obj_last_response->found);
        }
        $this->obj_schema = null; // Consume Schema
        return $arr_mapped_results;
    }

    /**
     * Apply project and namespace to a query
     *
     * @param \stdClass $obj_request
     * @return \stdClass
     */
    private function applyPartition(\stdClass $obj_request) {
        $obj_request->partitionId = (object)[
            'projectId' => $this->str_dataset_id
        ];
        if(null !== $this->str_namespace) {
            $obj_request->partitionId->namespaceId = $this->str_namespace;
        }
        return $obj_request;
    }

    /**
     * If we are in a transaction, apply it to the request object
     *
     * @todo Deal with read consistency
     *
     * @param $obj_request
     * @return mixed
     */
    private function applyTransaction(\stdClass $obj_request) {
        if(null !== $this->str_next_transaction) {
            $obj_request->readOptions = (object)[
                // 'readConsistency' => $options->getReadConsistency(),
                "transaction" => $this->str_next_transaction
            ];
            $this->str_next_transaction = null;
        }
        return $obj_request;
    }

    /**
     * Add Parameters to a GQL Query object
     *
     * @param \stdClass $obj_query
     * @param array $arr_params
     */
    private function addParamsToQuery(\stdClass $obj_query, array $arr_params)
    {
        if(count($arr_params) > 0) {
            $obj_bindings = new \stdClass();
            foreach ($arr_params as $str_name => $mix_value) {
                if('startCursor' == $str_name || 'endCursor' == $str_name) {
                    $obj_bindings->{$str_name} = (object)['cursor' => (string)$mix_value];
                } else {
                    $obj_bindings->{$str_name} = (object)['value' => $this->buildQueryParamValue($mix_value)];
                }
            }
            $obj_query->namedBindings = $obj_bindings;
        }
    }

    /**
     * Build a JSON representation of a value
     *
     * @param $mix_value
     * @return \stdClass
     */
    private function buildQueryParamValue($mix_value)
    {
        $obj_val = new \stdClass();
        $str_type = gettype($mix_value);
        switch($str_type) {
            case 'boolean':
                $obj_val->booleanValue = $mix_value;
                break;

            case 'integer':
                $obj_val->integerValue = $mix_value;
                break;

            case 'double':
                $obj_val->doubleValue = $mix_value;
                break;

            case 'string':
                $obj_val->stringValue = $mix_value;
                break;

            case 'array':
                throw new \InvalidArgumentException('Unexpected array parameter');

            case 'object':
                $this->configureObjectValueParamForQuery($obj_val, $mix_value);
                break;

            case 'NULL':
                $obj_val->nullValue = null;
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
     * @param object $obj_val
     * @param object $mix_value
     */
    protected function configureObjectValueParamForQuery($obj_val, $mix_value)
    {
        if($mix_value instanceof Entity) {
            /** @var Entity $mix_value */
            $obj_val->keyValue = $this->applyPartition((object)['path' => $this->createMapper()->buildKeyPath($mix_value)]);
        } elseif ($mix_value instanceof \DateTime) {
            $obj_val->timestampValue = $mix_value->format(\GDS\Mapper\RESTv1::DATETIME_FORMAT);
        } elseif (method_exists($mix_value, '__toString')) {
            $obj_val->stringValue = $mix_value->__toString();
        } else {
            throw new \InvalidArgumentException('Unexpected, non-string-able object parameter: ' . get_class($mix_value));
        }
    }

    /**
     * Get the end cursor from the last response
     *
     * @return mixed
     */
    public function getEndCursor()
    {
        if(isset($this->obj_last_response->batch) && isset($this->obj_last_response->batch->endCursor)) {
            return $this->obj_last_response->batch->endCursor;
        }
        return null;
    }

    /**
     * Create a mapper that's right for this Gateway
     *
     * @return \GDS\Mapper\RESTv1
     */
    protected function createMapper()
    {
        return (new \GDS\Mapper\RESTv1())->setSchema($this->obj_schema);
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
        $this->executePostRequest('beginTransaction');
        if(isset($this->obj_last_response->transaction)) {
            return $this->obj_last_response->transaction;
        }
        return null;
    }

    /**
     * Get the base url from the client object.
     *
     * Note: If for some reason the client's base URL is not set then we will return the default endpoint.
     *
     * @return string
     */
    protected function getBaseUrl() {
        $str_base_url = $this->obj_http_client->getConfig(self::CONFIG_CLIENT_BASE_URL);
        if (!empty($str_base_url)) {
            return $str_base_url;
        }

        return self::DEFAULT_BASE_URL;
    }

    /**
     * Build a URL for a Datastore action
     *
     * @param $str_action
     * @return string
     */
    private function actionUrl($str_action)
    {
        return $this->getBaseUrl() . '/v1/projects/' . $this->str_dataset_id . ':' . $str_action;
    }

}