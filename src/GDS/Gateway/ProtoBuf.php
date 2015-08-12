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
use GDS\Mapper\ProtoBufGQLParser;
use google\appengine\datastore\v4\BeginTransactionRequest;
use google\appengine\datastore\v4\BeginTransactionResponse;
use google\appengine\datastore\v4\CommitRequest;
use google\appengine\datastore\v4\CommitRequest\Mode;
use google\appengine\datastore\v4\CommitResponse;
use google\appengine\datastore\v4\Key;
use google\appengine\datastore\v4\LookupRequest;
use google\appengine\datastore\v4\LookupResponse;
use google\appengine\datastore\v4\PropertyFilter;
use google\appengine\datastore\v4\QueryResultBatch;
use google\appengine\datastore\v4\RunQueryRequest;
use google\appengine\datastore\v4\RunQueryResponse;
use google\appengine\datastore\v4\Value;
use google\appengine\runtime\ApiProxy;
use google\appengine\runtime\ApplicationError;
use google\net\ProtocolMessage;

/**
 * Protocol Buffer v4 Datastore Gateway
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS\Gateway
 */
class ProtoBuf extends \GDS\Gateway
{

    /**
     * Batch size for un-limited queries
     */
    const BATCH_SIZE = 1000;

    /**
     * Set up the dataset and optional namespace
     *
     * @todo Review use of $_SERVER.
     * Google propose a 'better' way of auto detecting app id,
     * but it's not perfect (does not work) in the dev environment
     * \google\appengine\api\app_identity\AppIdentityService::getApplicationId();
     *
     * @param null|string $str_dataset
     * @param null|string $str_namespace
     * @throws \Exception
     */
    public function __construct($str_dataset = null, $str_namespace = null)
    {
        if(null === $str_dataset) {
            if(isset($_SERVER['APPLICATION_ID'])) {
                $this->str_dataset_id = $_SERVER['APPLICATION_ID'];
            } else {
                throw new \Exception('Could not determine DATASET, please pass to ' . get_class($this) . '::__construct()');
            }
        } else {
            $this->str_dataset_id = $str_dataset;
        }
        $this->str_namespace = $str_namespace;
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
        $obj_request = $this->setupCommit();
        $obj_mutation = $obj_request->mutableDeprecatedMutation();
        $arr_auto_id_required = [];
        foreach($arr_entities as $obj_gds_entity) {
            if(null === $obj_gds_entity->getKeyId() && null === $obj_gds_entity->getKeyName()) {
                $obj_entity = $obj_mutation->addInsertAutoId();
                $arr_auto_id_required[] = $obj_gds_entity; // maintain reference to the array of requested auto-ids
            } else {
                $obj_entity = $obj_mutation->addUpsert();
            }
            $this->applyNamespace($obj_entity->mutableKey());
            $this->determineMapper($obj_gds_entity)->mapToGoogle($obj_gds_entity, $obj_entity);
        }
        $this->execute('Commit', $obj_request, new CommitResponse());
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
        foreach($this->obj_last_response->getDeprecatedMutationResult()->getInsertAutoIdKeyList() as $obj_key) {
            $arr_key_path = $obj_key->getPathElementList();
            $obj_path_end = end($arr_key_path);
            $arr_ids[] = $obj_path_end->getId();
        }
        return $arr_ids;
    }

    /**
     * Apply dataset and namespace ("partition") to an object
     *
     * Usually a Key or RunQueryRequest
     *
     * @param object $obj_target
     * @return mixed
     */
    private function applyNamespace($obj_target)
    {
        $obj_partition = $obj_target->mutablePartitionId();
        $obj_partition->setDatasetId($this->str_dataset_id);
        if(null !== $this->str_namespace) {
            $obj_partition->setNamespace($this->str_namespace);
        }
        return $obj_target;
    }

    /**
     * Apply a transaction to an object
     *
     * @param $obj
     * @return mixed
     */
    private function applyTransaction($obj)
    {
        if(null !== $this->str_next_transaction) {
            $obj->setTransaction($this->str_next_transaction);
            $this->str_next_transaction = null;
        }
        return $obj;
    }

    /**
     * Set up a RunQueryRequest
     *
     * @todo setReadConsistency
     * @todo Be more intelligent about when we set the suggested batch size (e.g. on LIMITed queries)
     *
     * @return RunQueryRequest
     */
    private function setupRunQuery()
    {
        $obj_request = ($this->applyNamespace(new RunQueryRequest()));
        $obj_request->setSuggestedBatchSize(self::BATCH_SIZE); // Avoid having to run multiple batches
        $this->applyTransaction($obj_request->mutableReadOptions()); // ->setReadConsistency('some-val');
        return $obj_request;
    }

    /**
     * Set up a LookupRequest
     *
     * @todo setReadConsistency
     *
     * @return LookupRequest
     */
    private function setupLookup()
    {
        $obj_request = new LookupRequest();
        $this->applyTransaction($obj_request->mutableReadOptions()); // ->setReadConsistency('some-val');
        return $obj_request;
    }

    /**
     * Set up a commit request
     *
     * @return CommitRequest
     */
    private function setupCommit()
    {
        $obj_commit_request = new CommitRequest();
        if(null === $this->str_next_transaction) {
            $obj_commit_request->setMode(Mode::NON_TRANSACTIONAL);
        } else {
            $obj_commit_request->setMode(Mode::TRANSACTIONAL);
            $this->applyTransaction($obj_commit_request);
        }
        return $obj_commit_request;
    }

    /**
     * Execute a method against the Datastore
     *
     * Use Google's static ApiProxy method
     *
     * Will attempt to convert GQL queries in local development environments
     *
     * @param $str_method
     * @param ProtocolMessage $obj_request
     * @param ProtocolMessage $obj_response
     * @return mixed
     * @throws ApplicationError
     * @throws \google\appengine\runtime\CapabilityDisabledError
     * @throws \google\appengine\runtime\FeatureNotEnabledError
     * @throws Contention
     */
    private function execute($str_method, ProtocolMessage $obj_request, ProtocolMessage $obj_response)
    {
        try {
            ApiProxy::makeSyncCall('datastore_v4', $str_method, $obj_request, $obj_response, 60);
            $this->obj_last_response = $obj_response;
        } catch (ApplicationError $obj_exception) {
            $this->obj_last_response = NULL;
            if($obj_request instanceof RunQueryRequest && 'GQL not supported.' === $obj_exception->getMessage()) {
                $this->executeGqlAsBasicQuery($obj_request); // recursive
            } elseif (FALSE !== strpos($obj_exception->getMessage(), 'too much contention') || FALSE !== strpos($obj_exception->getMessage(), 'Concurrency')) {
                // LIVE: "too much contention on these datastore entities. please try again." LOCAL : "Concurrency exception."
                throw new Contention('Datastore contention', 409, $obj_exception);
            } else {
                throw $obj_exception;
            }
        }
        return $this->obj_last_response;
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
        $obj_request = $this->setupLookup();
        foreach($arr_key_parts as $mix_key_part) {
            $obj_key = $obj_request->addKey();
            $this->applyNamespace($obj_key);
            $obj_kpe = $obj_key->addPathElement();
            $obj_kpe->setKind($this->obj_schema->getKind());
            $obj_kpe->$str_setter($mix_key_part);
        }
        $this->execute('Lookup', $obj_request, new LookupResponse());
        $arr_mapped_results = $this->createMapper()->mapFromResults($this->obj_last_response->getFoundList());
        $this->obj_schema = null; // Consume Schema
        return $arr_mapped_results;
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
        $obj_request = $this->setupCommit();
        $obj_mutation = $obj_request->mutableDeprecatedMutation();
        foreach($arr_entities as $obj_gds_entity) {
            $this->applyNamespace(
                $obj_mapper->configureGoogleKey(
                    $obj_mutation->addDelete(), $obj_gds_entity
                )
            );
        }
        $this->execute('Commit', $obj_request, new CommitResponse());
        $this->obj_schema = null;
        return TRUE; // really?
    }

    /**
     * Fetch some Entities, based on the supplied GQL and, optionally, parameters
     *
     * In local dev environments, we may convert the GQL query later.
     *
     * @todo Consider automatically handling multiple response batches?
     *
     * @param string $str_gql
     * @param array|null $arr_params
     * @return \GDS\Entity[]|null
     * @throws \Exception
     */
    public function gql($str_gql, $arr_params = null)
    {
        $obj_query_request = $this->setupRunQuery();
        $obj_gql_query = $obj_query_request->mutableGqlQuery();
        $obj_gql_query->setQueryString($str_gql);
        $obj_gql_query->setAllowLiteral(TRUE);
        if(null !== $arr_params) {
            $this->addParamsToQuery($obj_gql_query, $arr_params);
        }
        $obj_gql_response = $this->execute('RunQuery', $obj_query_request, new RunQueryResponse());
        $arr_mapped_results = $this->createMapper()->mapFromResults($obj_gql_response->getBatch()->getEntityResultList());
        $this->obj_schema = null; // Consume Schema
        return $arr_mapped_results;
    }

    /**
     * Take a GQL RunQuery request and convert to a standard RunQuery request
     *
     * Always expected to be called in the stack ->gql()->execute()->runGqlAsBasicQuery()
     *
     * @param ProtocolMessage $obj_gql_request
     * @return null
     * @throws \GDS\Exception\GQL
     */
    private function executeGqlAsBasicQuery(ProtocolMessage $obj_gql_request)
    {
        // Set up the new request
        $obj_query_request = $this->setupRunQuery();
        $obj_query = $obj_query_request->mutableQuery();

        // Transfer any transaction data to the new request
        /** @var RunQueryRequest $obj_gql_request */
        if($obj_gql_request->mutableReadOptions()->hasTransaction()) {
            $obj_query_request->mutableReadOptions()->setTransaction($obj_gql_request->mutableReadOptions()->getTransaction());
        }

        // Parse the GQL string
        $obj_gql_query = $obj_gql_request->getGqlQuery();
        $obj_parser = new ProtoBufGQLParser();
        $obj_parser->parse($obj_gql_query->getQueryString(), $obj_gql_query->getNameArgList());

        // Start applying to the new RunQuery request
        $obj_query->addKind()->setName($obj_parser->getKind());
        foreach($obj_parser->getOrderBy() as $arr_order_by) {
            $obj_query->addOrder()->setDirection($arr_order_by['direction'])->mutableProperty()->setName($arr_order_by['property']);
        }

        // Limits, Offsets, Cursors
        $obj_parser->getLimit() && $obj_query->setLimit($obj_parser->getLimit());
        $obj_parser->getOffset() && $obj_query->setOffset($obj_parser->getOffset());
        $obj_parser->getStartCursor() && $obj_query->setStartCursor($obj_parser->getStartCursor());
        // @todo @ $obj_query->setEndCursor();

        // Filters
        $int_filters = count($obj_parser->getFilters());
        if(1 === $int_filters) {
            $this->configureFilterFromGql($obj_query->mutableFilter()->mutablePropertyFilter(), $obj_parser->getFilters()[0]);
        } else if (1 < $int_filters) {
            $obj_composite_filter = $obj_query->mutableFilter()->mutableCompositeFilter()->setOperator(\google\appengine\datastore\v4\CompositeFilter\Operator::AND_);
            foreach ($obj_parser->getFilters() as $arr_filter) {
                $this->configureFilterFromGql($obj_composite_filter->addFilter()->mutablePropertyFilter(), $arr_filter);
            }
        }
        return $this->execute('RunQuery', $obj_query_request, new RunQueryResponse());
    }

    /**
     * @param PropertyFilter $obj_filter
     * @param $arr_filter
     */
    private function configureFilterFromGql(PropertyFilter $obj_filter, $arr_filter)
    {
        $obj_filter->mutableProperty()->setName($arr_filter['lhs']);
        $mix_value = $arr_filter['rhs'];
        if($mix_value instanceof Value) {
            $obj_filter->mutableValue()->mergeFrom($mix_value);
        } else {
            $obj_filter->mutableValue()->setStringValue($mix_value); // @todo Improve type detection using Schema
        }
        $obj_filter->setOperator($arr_filter['op']);
    }

    /**
     * Add Parameters to a GQL Query object
     *
     * @param \google\appengine\datastore\v4\GqlQuery $obj_query
     * @param array $arr_params
     */
    private function addParamsToQuery(\google\appengine\datastore\v4\GqlQuery $obj_query, array $arr_params)
    {
        if(count($arr_params) > 0) {
            foreach ($arr_params as $str_name => $mix_value) {
                $obj_arg = $obj_query->addNameArg();
                $obj_arg->setName($str_name);
                if ('startCursor' == $str_name) {
                    $obj_arg->setCursor($mix_value);
                } else {
                    $this->configureValueParamForQuery($obj_arg->mutableValue(), $mix_value);
                }
            }
        }
    }

    /**
     * Configure a Value parameter, based on the supplied object-type value
     *
     * @todo Re-use one Mapper instance
     *
     * @param \google\appengine\datastore\v4\Value $obj_val
     * @param object $mix_value
     */
    protected function configureObjectValueParamForQuery($obj_val, $mix_value)
    {
        if($mix_value instanceof Entity) {
            $obj_key_value = $obj_val->mutableKeyValue();
            $this->createMapper()->configureGoogleKey($obj_key_value, $mix_value);
            $this->applyNamespace($obj_key_value);
        } elseif ($mix_value instanceof \DateTime) {
            $obj_val->setTimestampMicrosecondsValue($mix_value->format('Uu'));
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
     * @return \GDS\Mapper\ProtoBuf
     */
    protected function createMapper()
    {
        return (new \GDS\Mapper\ProtoBuf())->setSchema($this->obj_schema);
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
        $obj_request = new BeginTransactionRequest();
        if($bol_cross_group) {
            $obj_request->setCrossGroup(TRUE);
        }
        $obj_response = $this->execute('BeginTransaction', $obj_request, new BeginTransactionResponse());
        return isset($obj_response->transaction) ? $obj_response->transaction : null;
    }
}