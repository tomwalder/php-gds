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
 * Map between Google Entity and GDS Entity data/objects
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 */
class Mapper
{

    /**
     * @var Schema
     */
    private $obj_schema = NULL;

    /**
     * Schema required on construction
     *
     * @param Schema $obj_schema
     */
    public function __construct(Schema $obj_schema)
    {
        $this->obj_schema = $obj_schema;
    }

    /**
     * Create a Datastore Entity from a GDS Entity
     *
     * @param Entity $obj_gds_entity
     * @return \Google_Service_Datastore_Entity
     */
    public function mapGoogleEntity(Entity $obj_gds_entity)
    {
        $obj_entity = new \Google_Service_Datastore_Entity();
        $obj_entity->setKey($this->createKey($obj_gds_entity));
        $obj_entity->setProperties($this->createProperties($obj_gds_entity));
        return $obj_entity;
    }

    /**
     * Create an array of Datastore Entity from an array of GDS Entities
     *
     * @param array $arr_gds_entities
     * @return \Google_Service_Datastore_Entity[]
     */
    public function mapGoogleEntities(array $arr_gds_entities)
    {
        $arr_entities = [];
        foreach($arr_gds_entities as $obj_gds_entity) {
            $arr_entities[] = $this->mapGoogleEntity($obj_gds_entity);
        }
        return $arr_entities;
    }

    /**
     * Create an Entity Key from a GDS Entity, with a Kind any any existing key data
     *
     * @param Entity $obj_gds_entity
     * @return \Google_Service_Datastore_Key
     */
    public function createKey(Entity $obj_gds_entity)
    {
        $obj_key = new \Google_Service_Datastore_Key();
        $obj_key->setPath($this->buildKeyPath($obj_gds_entity));
        return $obj_key;
    }

    /**
     * Create a fully qualified Key path
     *
     * @param Entity $obj_gds_entity
     * @param bool $bol_first_node
     * @return array
     * @throws \Exception
     */
    public function buildKeyPath(Entity $obj_gds_entity, $bol_first_node = TRUE)
    {
        $str_kind = $obj_gds_entity->getKind();
        if(NULL === $str_kind) {
            if($bol_first_node) {
                $str_kind = $this->obj_schema->getKind();
            } else {
                throw new \Exception('Could not build full key path, no Kind set on (nth node) GDS Entity');
            }
        }

        // Build the first node in the Key Path from this entity
        $arr_full_path = [$this->createKeyPathElement([
            'kind' => $str_kind,
            'id' => $obj_gds_entity->getKeyId(),
            'name' => $obj_gds_entity->getKeyName()
        ])];

        // Add any ancestors to the Key Path
        $mix_ancestry = $obj_gds_entity->getAncestry();
        if(is_array($mix_ancestry)) {
            foreach ((array)$obj_gds_entity->getAncestry() as $arr_ancestor_element) {
                array_unshift($arr_full_path, $this->createKeyPathElement($arr_ancestor_element));
            }
        } elseif ($mix_ancestry instanceof Entity) {
            $arr_full_path = array_merge($this->buildKeyPath($mix_ancestry, FALSE), $arr_full_path);
        }
        return $arr_full_path;
    }

    /**
     * Create a Key Path Element from array
     *
     * @param array $arr_kpe
     * @return \Google_Service_Datastore_KeyPathElement
     */
    private function createKeyPathElement(array $arr_kpe)
    {
        $arr_kpe = array_merge([
            'kind' => NULL,
            'id' => NULL,
            'name' => NULL
        ], $arr_kpe);
        $obj_element = new \Google_Service_Datastore_KeyPathElement();
        $obj_element->setKind($arr_kpe['kind']);
        $obj_element->setId($arr_kpe['id']);
        $obj_element->setName($arr_kpe['name']);
        return $obj_element;
    }

    /**
     * Create an array of Entity Key from an array of GDS Entities
     *
     * @param array $arr_gds_entities
     * @return array
     */
    public function createKeys(array $arr_gds_entities)
    {
        $arr_keys = [];
        foreach($arr_gds_entities as $obj_gds_entity) {
            $arr_keys[] = $this->createKey($obj_gds_entity);
        }
        return $arr_keys;
    }

    /**
     * Build & return the properties for the Entity
     *
     * @param Entity $obj_gds_entity
     * @return array
     * @throws \Exception
     */
    private function createProperties(Entity $obj_gds_entity)
    {
        $arr_property_map = [];
        $arr_field_defs = $this->obj_schema->getProperties();
        foreach($obj_gds_entity->getData() as $str_field_name => $mix_value) {
            if(isset($arr_field_defs[$str_field_name])) {
                $arr_property_map[$str_field_name] = $this->createProperty($arr_field_defs[$str_field_name], $mix_value);
            } else {
                switch(gettype($mix_value)) {
                    case 'boolean':
                        $int_dynamic_type = Schema::PROPERTY_BOOLEAN;
                        break;

                    case 'integer':
                        $int_dynamic_type = Schema::PROPERTY_INTEGER;
                        break;

                    case 'double':
                        $int_dynamic_type = Schema::PROPERTY_DOUBLE;
                        break;

                    case 'string':
                        $int_dynamic_type = Schema::PROPERTY_STRING;
                        break;

                    case 'array':
                        $int_dynamic_type = Schema::PROPERTY_STRING_LIST;
                        break;

                    case 'object':
                        if($mix_value instanceof \DateTime) {
                            $int_dynamic_type = Schema::PROPERTY_DATETIME;
                            break;
                        }
                        // No break on purpose - 'other' objects will be cast to string ;(

                    case 'resource':
                    case 'NULL':
                    case 'unknown type':
                    default:
                        trigger_error('Unsupported dynamic type, casting to string: ' . gettype($mix_value), E_USER_WARNING);
                        $int_dynamic_type = Schema::PROPERTY_STRING;
                        $mix_value = (string)$mix_value;
                }
                $arr_property_map[$str_field_name] = $this->createProperty(['type' => $int_dynamic_type, 'index' => TRUE], $mix_value);
            }
        }
        return $arr_property_map;
    }

    /**
     * Create a property object
     *
     * @param array $arr_field_def
     * @param $mix_value
     * @return mixed
     */
    private function createProperty(array $arr_field_def, $mix_value)
    {
        $obj_property = new \Google_Service_Datastore_Property();

        // Indexed?
        $bol_index = TRUE;
        if(isset($arr_field_def['index']) && FALSE === $arr_field_def['index']) {
            $bol_index = FALSE;
        }
        $obj_property->setIndexed($bol_index);

        switch ($arr_field_def['type']) {
            case Schema::PROPERTY_STRING:
                $obj_property->setStringValue((string)$mix_value);
                break;

            case Schema::PROPERTY_INTEGER:
                $obj_property->setIntegerValue((int)$mix_value);
                break;

            case Schema::PROPERTY_DATETIME:
                if($mix_value instanceof \DateTime) {
                    $obj_dtm = $mix_value;
                } else {
                    $obj_dtm = new \DateTime($mix_value);
                }
                $obj_property->setDateTimeValue($obj_dtm->format(\DateTime::ATOM));
                break;

            case Schema::PROPERTY_DOUBLE:
            case Schema::PROPERTY_FLOAT:
                $obj_property->setDoubleValue(floatval($mix_value));
                break;

            case Schema::PROPERTY_BOOLEAN:
                $obj_property->setBooleanValue((bool)$mix_value);
                break;

            case Schema::PROPERTY_STRING_LIST:
                $obj_property->setIndexed(NULL); // Ensure we only index the values, not the list
                $arr_values = [];
                foreach ((array)$mix_value as $str) {
                    $obj_value = new \Google_Service_Datastore_Value();
                    $obj_value->setStringValue($str);
                    $obj_value->setIndexed($bol_index);
                    $arr_values[] = $obj_value;
                }
                $obj_property->setListValue($arr_values);
                break;

            default:
                throw new \RuntimeException('Unable to process field type: ' . $arr_field_def['type']);
        }
        return $obj_property;
    }

    /**
     * Map a single result out of the Raw response data array into a supplied Entity object
     *
     * @todo Consider comparing results Kind with Schema Kind (and throwing Exception)
     *
     * @param \Google_Service_Datastore_EntityResult $obj_result
     * @param Entity $obj_gds_entity
     * @return Entity
     * @throws \Exception
     */
    public function mapGDSEntity($obj_result, Entity $obj_gds_entity)
    {
        if(isset($obj_result['entity']['key']['path'])) {
            $arr_path = $obj_result['entity']['key']['path'];

            // Key for 'self' (the last part of the KEY PATH)
            $arr_path_end = end($arr_path);
            if(isset($arr_path_end['id'])) {
                $obj_gds_entity->setKeyId($arr_path_end['id']);
            }
            if(isset($arr_path_end['name'])) {
                $obj_gds_entity->setKeyName($arr_path_end['name']);
            }

            // Ancestors?
            $int_path_elements = count($arr_path);
            if($int_path_elements > 1) {
                $arr_anc_path = [];
                foreach ($arr_path as $arr_kpe) {
                    $arr_anc_path[] = [
                        'kind' => $arr_kpe['kind'],
                        'id' => $arr_kpe['id'],
                        'name' => $arr_kpe['name']
                    ];
                }
                $obj_gds_entity->setAncestry(array_slice($arr_anc_path, 0, $int_path_elements - 1));
            }

        } else {
            throw new \RuntimeException("No path for Entity Key?");
        }

        $arr_property_definitions = $this->obj_schema->getProperties();
        foreach ((array)$obj_result['entity']['properties'] as $str_field => $obj_property) {
            /** @var $obj_property \Google_Service_Datastore_Property */
            if (isset($arr_property_definitions[$str_field])) {
                $obj_gds_entity->__set($str_field, $this->extractPropertyValue($arr_property_definitions[$str_field]['type'], $obj_property));
            } else {
                $obj_gds_entity->__set($str_field, $this->extractPropertyValue(Schema::PROPERTY_DETECT, $obj_property));
            }
        }
        return $obj_gds_entity;
    }

    /**
     * Extract a single property value from a Property object
     *
     * @param $int_type
     * @param \Google_Service_Datastore_Property $obj_property
     * @return array
     * @throws \Exception
     */
    private function extractPropertyValue($int_type, \Google_Service_Datastore_Property $obj_property)
    {
        switch ($int_type) {
            case Schema::PROPERTY_STRING:
                return $obj_property->getStringValue();

            case Schema::PROPERTY_INTEGER:
                return $obj_property->getIntegerValue();

            case Schema::PROPERTY_DATETIME:
                return $obj_property->getDateTimeValue();

            case Schema::PROPERTY_DOUBLE:
            case Schema::PROPERTY_FLOAT:
                return $obj_property->getDoubleValue();

            case Schema::PROPERTY_BOOLEAN:
                return $obj_property->getBooleanValue();

            case Schema::PROPERTY_STRING_LIST:
                $arr_values = $obj_property->getListValue();
                if(NULL !== $arr_values) {
                    $arr = [];
                    foreach ($arr_values as $obj_val) {
                        /** @var $obj_val \Google_Service_Datastore_Property */
                        $arr[] = $obj_val->getStringValue();
                    }
                    return $arr;
                }
                return NULL;

            case Schema::PROPERTY_DETECT:
                foreach([Schema::PROPERTY_STRING, Schema::PROPERTY_INTEGER, Schema::PROPERTY_DATETIME, Schema::PROPERTY_DOUBLE, Schema::PROPERTY_BOOLEAN] as $int_field_type) {
                    $mix_val = $this->extractPropertyValue($int_field_type, $obj_property);
                    if(NULL !== $mix_val) {
                        return $mix_val;
                    }
                }
                return NULL;
        }
        throw new \Exception('Unsupported field type: ' . $int_type);
    }

}