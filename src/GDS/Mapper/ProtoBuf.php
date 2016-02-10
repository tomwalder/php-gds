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
use google\appengine\datastore\v4\EntityResult;
use google\appengine\datastore\v4\Key;
use google\appengine\datastore\v4\Value;

/**
 * Protocol Buffer v4 Mapper
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class ProtoBuf extends \GDS\Mapper
{

    /**
     * Map from GDS to Google Protocol Buffer
     *
     * @param Entity $obj_gds_entity
     * @param \google\appengine\datastore\v4\Entity $obj_entity
     */
    public function mapToGoogle(Entity $obj_gds_entity, \google\appengine\datastore\v4\Entity $obj_entity)
    {
        // Key
        $this->configureGoogleKey($obj_entity->mutableKey(), $obj_gds_entity);

        // Properties
        $arr_field_defs = $this->obj_schema->getProperties();
        foreach($obj_gds_entity->getData() as $str_field_name => $mix_value) {
            $obj_prop = $obj_entity->addProperty();
            $obj_prop->setName($str_field_name);
            $obj_val = $obj_prop->mutableValue();
            if(isset($arr_field_defs[$str_field_name])) {
                $this->configureGooglePropertyValue($obj_val, $arr_field_defs[$str_field_name], $mix_value);
            } else {
                $arr_dynamic_data = $this->determineDynamicType($mix_value);
                $this->configureGooglePropertyValue($obj_val, ['type' => $arr_dynamic_data['type'], 'index' => TRUE], $arr_dynamic_data['value']);
            }
        }
    }

    /**
     * Map a single result out of the Raw response data into a supplied Entity object
     *
     * @todo Validate dynamic schema mapping in multi-kind responses like fetchEntityGroup()
     *
     * @param EntityResult $obj_result
     * @return Entity
     */
    public function mapOneFromResult($obj_result)
    {
        // Key & Ancestry
        list($obj_gds_entity, $bol_schema_match) = $this->createEntityWithKey($obj_result);

        // Properties
        $arr_property_definitions = $this->obj_schema->getProperties();
        foreach($obj_result->getEntity()->getPropertyList() as $obj_property) {
            /* @var $obj_property \google\appengine\datastore\v4\Property */
            $str_field = $obj_property->getName();
            if ($bol_schema_match && isset($arr_property_definitions[$str_field])) {
                $obj_gds_entity->__set($str_field, $this->extractPropertyValue($arr_property_definitions[$str_field]['type'], $obj_property->getValue()));
            } else {
                $obj_gds_entity->__set($str_field, $this->extractPropertyValue(Schema::PROPERTY_DETECT, $obj_property->getValue()));
            }
        }
        return $obj_gds_entity;
    }

    /**
     * Create & populate a GDS\Entity with key data
     *
     * @todo Validate dynamic mapping
     *
     * @param EntityResult $obj_result
     * @return array
     */
    private function createEntityWithKey(EntityResult $obj_result)
    {
        // Get the full key path
        $arr_key_path = $obj_result->getEntity()->getKey()->getPathElementList();

        // Key for 'self' (the last part of the KEY PATH)
        /* @var $obj_path_end \google\appengine\datastore\v4\Key\PathElement */
        $obj_path_end = array_pop($arr_key_path);
        if($obj_path_end->getKind() == $this->obj_schema->getKind()) {
            $bol_schema_match = TRUE;
            $obj_gds_entity = $this->obj_schema->createEntity();
        } else {
            $bol_schema_match = FALSE;
            $obj_gds_entity = (new \GDS\Entity())->setKind($obj_path_end->getKind());
        }

        // Set ID or Name (will always have one or the other)
        if($obj_path_end->hasId()) {
            $obj_gds_entity->setKeyId($obj_path_end->getId());
        } else {
            $obj_gds_entity->setKeyName($obj_path_end->getName());
        }

        // Ancestors?
        $int_ancestor_elements = count($arr_key_path);
        if($int_ancestor_elements > 0) {
            $arr_anc_path = [];
            foreach ($arr_key_path as $obj_kpe) {
                $arr_anc_path[] = [
                    'kind' => $obj_kpe->getKind(),
                    'id' => $obj_kpe->hasId() ? $obj_kpe->getId() : null,
                    'name' => $obj_kpe->hasName() ? $obj_kpe->getName() : null
                ];
            }
            $obj_gds_entity->setAncestry($arr_anc_path);
        }

        // Return whether or not the Schema matched
        return [$obj_gds_entity, $bol_schema_match];
    }

    /**
     * Populate a ProtoBuf Key from a GDS Entity
     *
     * @param Key $obj_key
     * @param Entity $obj_gds_entity
     * @return Key
     */
    public function configureGoogleKey(Key $obj_key, Entity $obj_gds_entity)
    {
        // Add any ancestors FIRST
        $mix_ancestry = $obj_gds_entity->getAncestry();
        if(is_array($mix_ancestry)) {
            // @todo Get direction right!
            foreach ($mix_ancestry as $arr_ancestor_element) {
                $this->configureGoogleKeyPathElement($obj_key->addPathElement(), $arr_ancestor_element);
            }
        } elseif ($mix_ancestry instanceof Entity) {
            // Recursive
            $this->configureGoogleKey($obj_key, $mix_ancestry);
        }

        // Root Key (must be the last in the chain)
        $this->configureGoogleKeyPathElement($obj_key->addPathElement(), [
            'kind' => $obj_gds_entity->getKind(),
            'id' => $obj_gds_entity->getKeyId(),
            'name' => $obj_gds_entity->getKeyName()
        ]);

        return $obj_key;
    }

    /**
     * Configure a Google Key Path Element object
     *
     * @param Key\PathElement $obj_path_element
     * @param array $arr_kpe
     */
    private function configureGoogleKeyPathElement(Key\PathElement $obj_path_element, array $arr_kpe)
    {
        $obj_path_element->setKind($arr_kpe['kind']);
        isset($arr_kpe['id']) && $obj_path_element->setId($arr_kpe['id']);
        isset($arr_kpe['name']) && $obj_path_element->setName($arr_kpe['name']);
    }

    /**
     * Populate a ProtoBuf Property Value from a GDS Entity field definition & value
     *
     * @todo compare with Google API implementation
     *
     * @param Value $obj_val
     * @param array $arr_field_def
     * @param $mix_value
     */
    private function configureGooglePropertyValue(Value $obj_val, array $arr_field_def, $mix_value)
    {
        // Indexed?
        $bol_index = TRUE;
        if(isset($arr_field_def['index']) && FALSE === $arr_field_def['index']) {
            $bol_index = FALSE;
        }
        $obj_val->setIndexed($bol_index);

        // null checks
        if(null === $mix_value) {
            return;
        }

        // Value
        switch ($arr_field_def['type']) {
            case Schema::PROPERTY_STRING:
                $obj_val->setStringValue((string)$mix_value);
                break;

            case Schema::PROPERTY_INTEGER:
                $obj_val->setIntegerValue((int)$mix_value);
                break;

            case Schema::PROPERTY_DATETIME:
                if($mix_value instanceof \DateTime) {
                    $obj_dtm = $mix_value;
                } else {
                    $obj_dtm = new \DateTime($mix_value);
                }
                $obj_val->setTimestampMicrosecondsValue($obj_dtm->format('Uu'));
                break;

            case Schema::PROPERTY_DOUBLE:
            case Schema::PROPERTY_FLOAT:
            $obj_val->setDoubleValue(floatval($mix_value));
                break;

            case Schema::PROPERTY_BOOLEAN:
                $obj_val->setBooleanValue((bool)$mix_value);
                break;

            case Schema::PROPERTY_GEOPOINT:
                $obj_val->mutableGeoPointValue()->setLatitude($mix_value[0])->setLongitude($mix_value[1]);
                break;

            case Schema::PROPERTY_STRING_LIST:
                $obj_val->clearIndexed(); // Ensure we only index the values, not the list
                foreach ((array)$mix_value as $str) {
                    $obj_val->addListValue()->setStringValue($str)->setIndexed($bol_index);
                }
                break;

            default:
                throw new \RuntimeException('Unable to process field type: ' . $arr_field_def['type']);
        }
    }

    /**
     * Extract a datetime value
     *
     * @todo Validate 32bit compatibility. Consider substr() or use bc math
     *
     * @param object $obj_property
     * @return mixed
     */
    protected function extractDatetimeValue($obj_property)
    {
        return date('Y-m-d H:i:s', $obj_property->getTimestampMicrosecondsValue() / 1000000);
    }

    /**
     * Extract a String List value
     *
     * @param object $obj_property
     * @return mixed
     */
    protected function extractStringListValue($obj_property)
    {
        $arr_values = $obj_property->getListValueList();
        if(count($arr_values) > 0) {
            $arr = [];
            foreach ($arr_values as $obj_val) {
                /** @var $obj_val Value */
                $arr[] = $obj_val->getStringValue();
            }
            return $arr;
        }
        return null;
    }

    /**
     * Extract a Geopoint value (lat/lon pair)
     *
     * @param \google\appengine\datastore\v4\Value $obj_property
     * @return Geopoint
     */
    protected function extractGeopointValue($obj_property)
    {
        $obj_gp_value = $obj_property->getGeoPointValue();
        return new Geopoint($obj_gp_value->getLatitude(), $obj_gp_value->getLongitude());
    }

    /**
     * Auto detect & extract a value
     *
     * @todo expand auto detect types
     *
     * @param Value $obj_property
     * @return mixed
     */
    protected function extractAutoDetectValue($obj_property)
    {
        if($obj_property->hasStringValue()) {
            return $obj_property->getStringValue();
        }
        if($obj_property->hasIntegerValue()) {
            return $obj_property->getIntegerValue();
        }
        if($obj_property->hasTimestampMicrosecondsValue()) {
            return $this->extractDatetimeValue($obj_property);
        }
        if($obj_property->hasDoubleValue()) {
            return $obj_property->getDoubleValue();
        }
        if($obj_property->hasBooleanValue()) {
            return $obj_property->getBooleanValue();
        }
        if($obj_property->hasGeoPointValue()) {
            return $this->extractGeopointValue($obj_property);
        }
        if($obj_property->getListValueSize() > 0) {
            return $this->extractStringListValue($obj_property);
        }
        // $this->extractPropertyValue($int_field_type, $obj_property); // Recursive detection call
        return null;
    }
}