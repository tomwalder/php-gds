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
namespace GDS\Mapper;
use GDS\Entity;
use GDS\Property\Geopoint;
use GDS\Schema;

/**
 * Mapper for GoogleAPIClient
 */
class GoogleAPIClient extends \GDS\Mapper
{

    /**
     * Create a Datastore Entity from a GDS Entity
     *
     * @param Entity $obj_gds_entity
     * @return \Google_Service_Datastore_Entity
     */
    public function mapToGoogle(Entity $obj_gds_entity)
    {
        $obj_entity = new \Google_Service_Datastore_Entity();
        $obj_entity->setKey($this->createKey($obj_gds_entity));
        $obj_entity->setProperties($this->createProperties($obj_gds_entity));
        return $obj_entity;
    }

    /**
     * Build & return the properties for the Entity
     *
     * @param Entity $obj_gds_entity
     * @return array
     * @throws \Exception
     */
    protected function createProperties(Entity $obj_gds_entity)
    {
        $arr_property_map = [];
        $arr_field_defs = $this->obj_schema->getProperties();
        foreach($obj_gds_entity->getData() as $str_field_name => $mix_value) {
            if(isset($arr_field_defs[$str_field_name])) {
                $arr_property_map[$str_field_name] = $this->createProperty($arr_field_defs[$str_field_name], $mix_value);
            } else {
                $arr_dynamic_data = $this->determineDynamicType($mix_value);
                $arr_property_map[$str_field_name] = $this->createProperty(['type' => $arr_dynamic_data['type'], 'index' => TRUE], $arr_dynamic_data['value']);
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
    protected function createProperty(array $arr_field_def, $mix_value)
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

            case Schema::PROPERTY_GEOPOINT:
                throw new \RuntimeException('Geopoint properties not supported over JSON API');
                break;

            case Schema::PROPERTY_STRING_LIST:
                $obj_property->setIndexed(null); // Ensure we only index the values, not the list
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
     * Create a Google Entity Key from a GDS Entity, with a Kind any any existing key data
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
     * @equivalent ProtoBuf::configureKey() ?
     *
     * @param Entity $obj_gds_entity
     * @param bool $bol_first_node
     * @return array
     * @throws \Exception
     */
    private function buildKeyPath(Entity $obj_gds_entity, $bol_first_node = TRUE)
    {
        $str_kind = $obj_gds_entity->getKind();
        if(null === $str_kind) {
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
    protected function createKeyPathElement(array $arr_kpe)
    {
        $obj_element = new \Google_Service_Datastore_KeyPathElement();
        $obj_element->setKind($arr_kpe['kind']);
        isset($arr_kpe['id']) && $obj_element->setId($arr_kpe['id']);
        isset($arr_kpe['name']) && $obj_element->setName($arr_kpe['name']);
        return $obj_element;
    }

    /**
     * Map a single result out of the Raw response data array into a supplied Entity object
     *
     * @param \Google_Service_Datastore_EntityResult $obj_result
     * @return Entity
     * @throws \Exception
     */
    public function mapOneFromResult($obj_result)
    {
        // Key & Ancestry
        list($obj_gds_entity, $bol_schema_match) = $this->createEntityWithKey($obj_result);

        // Properties
        $arr_property_definitions = $this->obj_schema->getProperties();
        foreach ((array)$obj_result['entity']['properties'] as $str_field => $obj_property) {
            /** @var $obj_property \Google_Service_Datastore_Property */
            if ($bol_schema_match && isset($arr_property_definitions[$str_field])) {
                $obj_gds_entity->__set($str_field, $this->extractPropertyValue($arr_property_definitions[$str_field]['type'], $obj_property));
            } else {
                $obj_gds_entity->__set($str_field, $this->extractPropertyValue(Schema::PROPERTY_DETECT, $obj_property));
            }
        }

        // Done
        return $obj_gds_entity;
    }

    /**
     * Create & populate a GDS\Entity with key data
     *
     * @param \Google_Service_Datastore_EntityResult $obj_result
     * @return array
     */
    private function createEntityWithKey(\Google_Service_Datastore_EntityResult $obj_result)
    {
        if(isset($obj_result['entity']['key']['path'])) {
            $arr_path = $obj_result['entity']['key']['path'];

            // Key for 'self' (the last part of the KEY PATH)
            $arr_path_end = array_pop($arr_path);

            // Kind
            if(isset($arr_path_end['kind'])) {
                if($arr_path_end['kind'] == $this->obj_schema->getKind()) {
                    $bol_schema_match = TRUE;
                    $obj_gds_entity = $this->obj_schema->createEntity();
                } else {
                    // Attempt to handle a non-schema-match
                    $bol_schema_match = FALSE;
                    $obj_gds_entity = (new \GDS\Entity())->setKind($arr_path_end['kind']);
                }
            } else {
                throw new \RuntimeException("No Kind for end(path) for Entity?");
            }

            // ID or Name
            if(isset($arr_path_end['id'])) {
                $obj_gds_entity->setKeyId($arr_path_end['id']);
            } elseif (isset($arr_path_end['name'])) {
                $obj_gds_entity->setKeyName($arr_path_end['name']);
            } else {
                throw new \RuntimeException("No KeyID or KeyName for Entity?");
            }

            // Ancestors?
            $int_path_elements = count($arr_path);
            if($int_path_elements > 0) {
                $arr_anc_path = [];
                foreach ($arr_path as $arr_kpe) {
                    $arr_anc_path[] = [
                        'kind' => $arr_kpe['kind'],
                        'id' => $arr_kpe['id'],
                        'name' => $arr_kpe['name']
                    ];
                }
                $obj_gds_entity->setAncestry($arr_anc_path);
            }
        } else {
            throw new \RuntimeException("No path for Entity Key?");
        }

        // Return whether or not the Schema matched
        return [$obj_gds_entity, $bol_schema_match];
    }

    /**
     * Extract a datetime value
     *
     * @param object $obj_property
     * @return mixed
     */
    protected function extractDatetimeValue($obj_property)
    {
        return $obj_property->getDateTimeValue();
    }

    /**
     * Extract a String List value
     *
     * @param object $obj_property
     * @return mixed
     */
    protected function extractStringListValue($obj_property)
    {
        $arr_values = $obj_property->getListValue();
        if(count($arr_values) > 0) {
            $arr = [];
            foreach ($arr_values as $obj_val) {
                /** @var $obj_val \Google_Service_Datastore_Property */
                $arr[] = $obj_val->getStringValue();
            }
            return $arr;
        }
        return null;
    }

    /**
     * Extract a Geopoint value (lat/lon pair)
     *
     * @param $obj_property
     * @return Geopoint
     * @throws \Exception
     */
    protected function extractGeopointValue($obj_property)
    {
        throw new \Exception("Geopoint data not supported with JSON API");
    }

    /**
     * Auto detect & extract a value
     *
     * @param object $obj_property
     * @return mixed
     */
    protected function extractAutoDetectValue($obj_property)
    {
        foreach([Schema::PROPERTY_STRING, Schema::PROPERTY_INTEGER, Schema::PROPERTY_DATETIME, Schema::PROPERTY_DOUBLE, Schema::PROPERTY_BOOLEAN, Schema::PROPERTY_STRING_LIST] as $int_field_type) {
            $mix_val = $this->extractPropertyValue($int_field_type, $obj_property); // Recursive detection call
            if(null !== $mix_val) {
                return $mix_val;
            }
        }
        return null;
    }

}
