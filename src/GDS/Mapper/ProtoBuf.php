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
use GDS\Schema;
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
     * @todo Consider comparing results Kind with Schema Kind (and throwing Exception)
     * @todo Review support for custom Entity classes
     *
     * @param \google\appengine\datastore\v4\EntityResult $obj_result
     * @return Entity
     * @throws \Exception
     */
    public function mapOneFromResult($obj_result)
    {
        $obj_gds_entity = new Entity();

        // Key & Ancestry
        $arr_key_path = $obj_result->getEntity()->getKey()->getPathElementList();
        $bol_schema_match = $this->configureGDSKey($arr_key_path, $obj_gds_entity);

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
     * Populate a GDS\Entity with key data
     *
     * @todo Validate dynamic mapping
     * @todo Upgrade to support ancestor keys
     *
     * @param \google\appengine\datastore\v4\Key\PathElement[] $arr_key_path
     * @param Entity $obj_gds_entity
     * @return bool
     * @throws \Exception
     */
    private function configureGDSKey(array $arr_key_path, Entity $obj_gds_entity)
    {
        // Key for 'self' (the last part of the KEY PATH)
        /* @var $obj_path_end \google\appengine\datastore\v4\Key\PathElement */
        $obj_path_end = end($arr_key_path);
        if($obj_path_end->getKind() == $this->obj_schema->getKind()) {
            $bol_schema_match = TRUE;
            $obj_gds_entity->setSchema($this->obj_schema);
        } else {
            $bol_schema_match = FALSE;
            $obj_gds_entity->setKind($obj_path_end->getKind());
        }

        // ID or Name
        if($obj_path_end->hasId()) {
            $obj_gds_entity->setKeyId($obj_path_end->getId());
        } elseif ($obj_path_end->hasName()) {
            $obj_gds_entity->setKeyName($obj_path_end->getName());
        } else {
            throw new \Exception('Entity without KeyID or KeyName');
        }

        // Ancestors?
        $int_path_elements = count($arr_key_path);
        if($int_path_elements > 1) {
            // print_r($arr_key_path); echo "<==>"; print_r($obj_path_end);
            throw new \Exception('Unimplemented, Entity with ancestors');
        }

        return $bol_schema_match;
    }

    /**
     * Populate a ProtoBuf Key from a GDS Entity
     *
     * @todo Support Ancestors
     *
     * @param Key $obj_key
     * @param Entity $obj_gds_entity
     * @return Key
     */
    public function configureGoogleKey(Key $obj_key, Entity $obj_gds_entity)
    {
        $obj_path_element = $obj_key->addPathElement();
        $obj_path_element->setKind($obj_gds_entity->getKind());
        if(NULL !== $obj_gds_entity->getKeyId()) {
            $obj_path_element->setId($obj_gds_entity->getKeyId());
        }
        if(NULL !== $obj_gds_entity->getKeyName()) {
            $obj_path_element->setName($obj_gds_entity->getKeyName());
        }
        if(NULL !== $obj_gds_entity->getAncestry()) {
            throw new \RuntimeException("Unimplemented: ancestor support");
        }
        return $obj_key;
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

            case Schema::PROPERTY_STRING_LIST:  // @todo FIXME FIXME
                $obj_val->setIndexed(NULL); // Ensure we only index the values, not the list
                $arr_values = [];
                foreach ((array)$mix_value as $str) {
                    $obj_value = new \Google_Service_Datastore_Value();
                    $obj_value->setStringValue($str);
                    $obj_value->setIndexed($bol_index);
                    $arr_values[] = $obj_value;
                }
                $obj_val->setListValue($arr_values);
                break;

            default:
                throw new \RuntimeException('Unable to process field type: ' . $arr_field_def['type']);
        }

    }

    /**
     * Extract a datetime value
     *
     * @todo FIXME FIXME FIXME validate date('', microseconds) ?
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
        return NULL;
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
        // $this->extractPropertyValue($int_field_type, $obj_property); // Recursive detection call
        return NULL;
    }
}