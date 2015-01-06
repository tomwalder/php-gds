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
 * Map between Entity and GDS Model data/objects
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
     * Create a Datastore Entity from a Model
     *
     * @param Model $obj_model
     * @return \Google_Service_Datastore_Entity
     */
    public function createFromModel(Model $obj_model)
    {
        $obj_entity = new \Google_Service_Datastore_Entity();
        $obj_entity->setKey($this->createKey($obj_model));
        $obj_entity->setProperties($this->createProperties($obj_model));
        return $obj_entity;
    }

    /**
     * Create an array of Datastore Entity from an array of Models
     *
     * @param array $arr_models
     * @return array
     */
    public function createFromModels(array $arr_models)
    {
        $arr_entities = [];
        foreach($arr_models as $obj_model) {
            $arr_entities[] = $this->createFromModel($obj_model);
        }
        return $arr_entities;
    }

    /**
     * Create an Entity Key from a Model, with a Kind any any existing key data
     *
     * @todo handle Ancestor keys
     *
     * @param Model $obj_model
     * @return \Google_Service_Datastore_Key
     */
    public function createKey(Model $obj_model)
    {
        $obj_path = new \Google_Service_Datastore_KeyPathElement();
        $obj_path->setKind($this->obj_schema->getKind());
        if (NULL !== $obj_model->getKeyId()) {
            $obj_path->setId($obj_model->getKeyId());
        } else if (NULL !== $obj_model->getKeyName()) {
            $obj_path->setName($obj_model->getKeyName());
        }
        $obj_key = new \Google_Service_Datastore_Key();
        $obj_key->setPath([$obj_path]);
        return $obj_key;
    }

    /**
     * Create an array of Entity Key from an array of Models
     *
     * @param array $arr_models
     * @return array
     */
    public function createKeys(array $arr_models)
    {
        $arr_keys = [];
        foreach($arr_models as $obj_model) {
            $arr_keys[] = $this->createKey($obj_model);
        }
        return $arr_keys;
    }

    /**
     * Build & return the properties for the Model
     *
     * @param Model $obj_model
     * @return array
     * @throws \Exception
     */
    private function createProperties(Model $obj_model)
    {
        $arr_property_map = [];
        $arr_field_defs = $this->obj_schema->getFields();
        foreach($obj_model->getData() as $str_field_name => $mix_value) {
            if(isset($arr_field_defs[$str_field_name])) {
                $arr_property_map[$str_field_name] = $this->createProperty($arr_field_defs[$str_field_name], $mix_value);
            } else {
                switch(gettype($mix_value)) {
                    case 'boolean':
                        $int_dynamic_type = Schema::FIELD_BOOLEAN;
                        break;

                    case 'integer':
                        $int_dynamic_type = Schema::FIELD_INTEGER;
                        break;

                    case 'double':
                        $int_dynamic_type = Schema::FIELD_DOUBLE;
                        break;

                    case 'string':
                        $int_dynamic_type = Schema::FIELD_STRING;
                        break;

                    case 'array':
                    case 'object':
                    case 'resource':
                    case 'NULL':
                    case 'unknown type':
                    default:
                        trigger_error('Unsupported dynamic type, casting to string: ' . gettype($mix_value), E_USER_WARNING);
                        $int_dynamic_type = Schema::FIELD_STRING;
                        $mix_value = (string)$mix_value;
                }
                $arr_property_map[$str_field_name] = $this->createProperty(['type' => $int_dynamic_type, 'index' => FALSE], $mix_value);
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
        $bol_index = FALSE;
        if(isset($arr_field_def['index']) && TRUE === $arr_field_def['index']) {
            $bol_index = TRUE;
        }
        $obj_property->setIndexed($bol_index);

        switch ($arr_field_def['type']) {
            case Schema::FIELD_STRING:
                $obj_property->setStringValue((string)$mix_value);
                break;

            case Schema::FIELD_INTEGER:
                $obj_property->setIntegerValue((int)$mix_value);
                break;

            case Schema::FIELD_DATETIME:
                $obj_property->setDateTimeValue((new \DateTime($mix_value))->format(\DateTime::ATOM));
                break;

            case Schema::FIELD_DOUBLE:
            case Schema::FIELD_FLOAT:
                $obj_property->setDoubleValue(floatval($mix_value));
                break;

            case Schema::FIELD_BOOLEAN:
                $obj_property->setBooleanValue((bool)$mix_value);
                break;

            case Schema::FIELD_STRING_LIST:
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
     * Map a single result out of the Raw response data array into a supplied Model object
     *
     * @todo Parent Keys
     *
     * @param $arr_result
     * @param Model $obj_model
     * @return Model
     * @throws \Exception
     */
    public function mapFromRawData($arr_result, Model $obj_model)
    {
        if(isset($arr_result['entity']['key']['path'])) {
            $arr_path = $arr_result['entity']['key']['path'];

            // KEY for 'self' (the last part of the KEY PATH)
            $arr_path_end = end($arr_path);
            if(isset($arr_path_end['id'])) {
                $obj_model->setKeyId($arr_path_end['id']);
            }
            if(isset($arr_path_end['name'])) {
                $obj_model->setKeyName($arr_path_end['name']);
            }

            // if(count($arr_path) > 1) {
            //     $arr_parent = [$arr_path[0]['kind'], $arr_path[0]['name']];
            //     $obj_model->setParent($arr_parent);
            // }
        } else {
            throw new \RuntimeException("No path for Entity Key?");
        }

        $arr_field_defs = $this->obj_schema->getFields();
        foreach ((array)$arr_result['entity']['properties'] as $str_field => $obj_property) {
            /** @var $obj_property \Google_Service_Datastore_Property */
            if (isset($arr_field_defs[$str_field])) {
                $obj_model->__set($str_field, $this->extractPropertyValue($arr_field_defs[$str_field]['type'], $obj_property));
            } else {
                $obj_model->__set($str_field, $this->extractPropertyValue(Schema::FIELD_DETECT, $obj_property));
            }
        }
        return $obj_model;
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
            case Schema::FIELD_STRING:
                return $obj_property->getStringValue();

            case Schema::FIELD_INTEGER:
                return $obj_property->getIntegerValue();

            case Schema::FIELD_DATETIME:
                return $obj_property->getDateTimeValue();

            case Schema::FIELD_DOUBLE:
            case Schema::FIELD_FLOAT:
                return $obj_property->getDoubleValue();

            case Schema::FIELD_BOOLEAN:
                return $obj_property->getBooleanValue();

            case Schema::FIELD_STRING_LIST:
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

            case Schema::FIELD_DETECT:
                foreach([Schema::FIELD_STRING, Schema::FIELD_INTEGER, Schema::FIELD_DATETIME, Schema::FIELD_DOUBLE, Schema::FIELD_BOOLEAN] as $int_field_type) {
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