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
 * Persists and retrieves Entities to/from Google Cloud Datastore.
 *
 * This class is designed to work FOR the \GDS\Store, it is not generally
 * expected to be used directly by the application developer
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 */
abstract class Gateway
{

    /**
     * The dataset ID
     *
     * @var string|null
     */
    protected $str_dataset_id = null;

    /**
     * Optional namespace (for multi-tenant applications)
     *
     * @var string|null
     */
    protected $str_namespace = null;

    /**
     * The last response - usually a Commit or Query response
     *
     * @var object|null
     */
    protected $obj_last_response = null;

    /**
     * The transaction ID to use on the next commit
     *
     * @var null|string
     */
    protected $str_next_transaction = null;

    /**
     * The current Schema
     *
     * @var Schema|null
     */
    protected $obj_schema = null;

    /**
     * An array of Mappers, keyed on Entity Kind
     *
     * @var \GDS\Mapper[]
     */
    protected $arr_kind_mappers = [];

    /**
     * Set the Schema to be used next (once?)
     *
     * @param Schema $obj_schema
     * @return $this
     */
    public function withSchema(Schema $obj_schema)
    {
        $this->obj_schema = $obj_schema;
        return $this;
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
     * Fetch one entity by Key ID
     *
     * @param $int_key_id
     * @return mixed
     */
    public function fetchById($int_key_id)
    {
        $arr_results = $this->fetchByIds([$int_key_id]);
        if(count($arr_results) > 0) {
            return $arr_results[0];
        }
        return null;
    }

    /**
     * Fetch entity data by Key Name
     *
     * @param $str_key_name
     * @return mixed
     */
    public function fetchByName($str_key_name)
    {
        $arr_results = $this->fetchByNames([$str_key_name]);
        if(count($arr_results) > 0) {
            return $arr_results[0];
        }
        return null;
    }

    /**
     * Delete an Entity
     *
     * @param Entity $obj_key
     * @return bool
     */
    public function delete(Entity $obj_key)
    {
        return $this->deleteMulti([$obj_key]);
    }

    /**
     * Put a single Entity into the Datastore
     *
     * @param Entity $obj_entity
     */
    public function put(Entity $obj_entity)
    {
        $this->putMulti([$obj_entity]);
    }

    /**
     * Put an array of Entities into the Datastore
     *
     * Consumes Schema
     *
     * @param \GDS\Entity[] $arr_entities
     * @throws \Exception
     */
    public function putMulti(array $arr_entities)
    {
        // Ensure all the supplied are Entities and have a Kind & Schema
        $this->ensureSchema($arr_entities);

        // Record the Auto-generated Key IDs against the GDS Entities.
        $this->mapAutoIDs($this->upsert($arr_entities));

        // Consume schema, clear kind mapper-map(!)
        $this->obj_schema = null;
        $this->arr_kind_mappers = [];
    }

    /**
     * Fetch one or more entities by KeyID
     *
     * Consumes Schema (deferred)
     *
     * @param array $arr_key_ids
     * @return array
     */
    public function fetchByIds(array $arr_key_ids)
    {
        return $this->fetchByKeyPart($arr_key_ids, 'setId');
    }

    /**
     * Fetch one or more entities by KeyName
     *
     * Consume Schema (deferred)
     *
     * @param array $arr_key_names
     * @return array
     */
    public function fetchByNames(array $arr_key_names)
    {
        return $this->fetchByKeyPart($arr_key_names, 'setName');
    }

    /**
     * Default Kind & Schema support for "new" Entities
     *
     * @param \GDS\Entity[] $arr_entities
     */
    protected function ensureSchema($arr_entities)
    {
        foreach($arr_entities as $obj_gds_entity) {
            if($obj_gds_entity instanceof Entity) {
                if (null === $obj_gds_entity->getKind()) {
                    $obj_gds_entity->setSchema($this->obj_schema);
                }
            } else {
                throw new \InvalidArgumentException('You gave me something other than GDS\Entity objects.. not gonna fly!');
            }
        }
    }

    /**
     * Determine Mapper (early stage [draft] support for cross-entity upserts)
     *
     * @param Entity $obj_gds_entity
     * @return Mapper
     */
    protected function determineMapper(Entity $obj_gds_entity)
    {
        $str_this_kind = $obj_gds_entity->getKind();
        if(!isset($this->arr_kind_mappers[$str_this_kind])) {
            $this->arr_kind_mappers[$str_this_kind] = $this->createMapper();
            if($this->obj_schema->getKind() != $str_this_kind) {
                $this->arr_kind_mappers[$str_this_kind]->setSchema($obj_gds_entity->getSchema());
            }
        }
        return $this->arr_kind_mappers[$str_this_kind];
    }

    /**
     * Record the Auto-generated Key IDs against the GDS Entities.
     *
     * @param \GDS\Entity[] $arr_auto_id_requested
     * @throws \Exception
     */
    protected function mapAutoIDs(array $arr_auto_id_requested)
    {
        if (!empty($arr_auto_id_requested)) {
            $arr_auto_ids = $this->extractAutoIDs();
            if(count($arr_auto_id_requested) === count($arr_auto_ids)) {
                foreach ($arr_auto_id_requested as $int_idx => $obj_gds_entity) {
                    $obj_gds_entity->setKeyId($arr_auto_ids[$int_idx]);
                }
            } else {
                throw new \Exception("Mismatch count of requested & returned Auto IDs");
            }
        }
    }

    /**
     * Part of our "add parameters to query" sequence.
     *
     * Shared between multiple Gateway implementations.
     *
     * @param $obj_val
     * @param $mix_value
     * @return $obj_val
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

            case 'null':
                $obj_val->setStringValue(null);
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
    abstract protected function configureObjectValueParamForQuery($obj_val, $mix_value);

    /**
     * Put an array of Entities into the Datastore. Return any that need AutoIDs
     *
     * @param \GDS\Entity[] $arr_entities
     * @return \GDS\Entity[]
     */
    abstract protected function upsert(array $arr_entities);

    /**
     * Extract Auto Insert IDs from the last response
     *
     * @return array
     */
    abstract protected function extractAutoIDs();

    /**
     * Fetch 1-many Entities, using the Key parts provided
     *
     * Consumes Schema
     *
     * @param array $arr_key_parts
     * @param $str_setter
     * @return mixed
     */
    abstract protected function fetchByKeyPart(array $arr_key_parts, $str_setter);

    /**
     * Delete 1-many entities
     *
     * @param array $arr_entities
     * @return mixed
     */
    abstract public function deleteMulti(array $arr_entities);

    /**
     * Fetch some Entities, based on the supplied GQL and, optionally, parameters
     *
     * @param string $str_gql
     * @param null|array $arr_params
     * @return mixed
     */
    abstract public function gql($str_gql, $arr_params = null);

    /**
     * Get the end cursor from the last response
     *
     * @return mixed
     */
    abstract public function getEndCursor();

    /**
     * Create a mapper that's right for this Gateway
     *
     * @return Mapper
     */
    abstract protected function createMapper();

    /**
     * Start a transaction
     *
     * @param bool $bol_cross_group
     * @return mixed
     */
    abstract public function beginTransaction($bol_cross_group = FALSE);

}