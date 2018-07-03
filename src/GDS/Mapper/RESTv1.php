<?php
/**
 * Copyright 2016 Tom Walder
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
 * Mapper for Datastore API v1, REST
 */
class RESTv1 extends \GDS\Mapper
{

    /**
     * This is the DateTime::format string required to support Datastore timestamps
     *
     * A timestamp in RFC3339 UTC "Zulu" format, accurate to nanoseconds. Example: "2014-10-02T15:01:23.045123456Z".
     */
    const DATETIME_FORMAT = 'Y-m-d\TH:i:s.u\Z';

    /**
     * Auto detect & extract a value
     *
     * @todo Support remaining types for auto extraction: keyValue, blobValue, entityValue
     *
     * @param object $obj_property
     * @return mixed
     */
    protected function extractAutoDetectValue($obj_property)
    {
        if(isset($obj_property->stringValue)) {
            return $obj_property->stringValue;
        }
        if(isset($obj_property->integerValue)) {
            return $obj_property->integerValue;
        }
        if(isset($obj_property->doubleValue)) {
            return $obj_property->doubleValue;
        }
        if(isset($obj_property->booleanValue)) {
            return $obj_property->booleanValue;
        }
        if(isset($obj_property->timestampValue)) {
            return $this->extractDatetimeValue($obj_property);
        }
        if(isset($obj_property->geoPointValue)) {
            return $this->extractGeopointValue($obj_property);
        }
        if(isset($obj_property->arrayValue)) {
            return $this->extractStringListValue($obj_property);
        }
        if(property_exists($obj_property, 'nullValue')) {
            return null;
        }
    }

    /**
     * Extract a datetime value
     *
     * We will lose accuracy
     * - past seconds in version 3.0
     * - past microseconds (down from nanoseconds) in version 4.0
     *
     * @param $obj_property
     * @return mixed
     */
    protected function extractDatetimeValue($obj_property)
    {
        $arr_matches = [];
        if(preg_match('/(.{19})\.?(\d{0,6}).*Z/', $obj_property->timestampValue, $arr_matches) > 0) {
            $obj_dtm = new \DateTime($arr_matches[1] . '.' . $arr_matches[2] . 'Z');
        } else {
            $obj_dtm = new \DateTime($obj_property->timestampValue);
        }
        return $obj_dtm;
    }

    /**
     * Extract a String List value
     *
     * @param $obj_property
     * @throws \Exception
     * @return array
     */
    protected function extractStringListValue($obj_property)
    {
        $arr_values = [];

        if (!isset($obj_property->arrayValue->values)) {
            return $arr_values;
        }

        foreach((array)$obj_property->arrayValue->values as $obj_value) {
            if(isset($obj_value->stringValue)) {
                $arr_values[] = $obj_value->stringValue;
            }
        }
        return $arr_values;
    }

    /**
     * Extract a Geopoint value
     *
     * @param $obj_property
     * @return Geopoint
     */
    protected function extractGeopointValue($obj_property)
    {
        return new Geopoint($obj_property->geoPointValue->latitude, $obj_property->geoPointValue->longitude);
    }

    /**
     * Map a single result out of the Raw response data array FROM Google TO a GDS Entity
     *
     * @param object $obj_result
     * @return Entity
     * @throws \Exception
     */
    public function mapOneFromResult($obj_result)
    {
        // Key & Ancestry
        list($obj_gds_entity, $bol_schema_match) = $this->createEntityWithKey($obj_result);
        /** @var Entity $obj_gds_entity */

        // Properties
        if(isset($obj_result->entity->properties)) {
            $arr_property_definitions = $this->obj_schema->getProperties();
            foreach ($obj_result->entity->properties as $str_field => $obj_property) {
                if ($bol_schema_match && isset($arr_property_definitions[$str_field])) {
                    $obj_gds_entity->__set($str_field, $this->extractPropertyValue($arr_property_definitions[$str_field]['type'], $obj_property));
                } else {
                    $obj_gds_entity->__set($str_field, $this->extractPropertyValue(Schema::PROPERTY_DETECT, $obj_property));
                }
            }
        }

        // Done
        return $obj_gds_entity;
    }

    /**
     * Create & populate a GDS\Entity with key data
     *
     * @param \stdClass $obj_result
     * @return array
     */
    private function createEntityWithKey(\stdClass $obj_result)
    {
        if(isset($obj_result->entity->key->path)) {
            $arr_path = $obj_result->entity->key->path;

            // Key for 'self' (the last part of the KEY PATH)
            $obj_path_end = array_pop($arr_path);

            // Kind
            if(isset($obj_path_end->kind)) {
                if($obj_path_end->kind == $this->obj_schema->getKind()) {
                    $bol_schema_match = TRUE;
                    $obj_gds_entity = $this->obj_schema->createEntity();
                } else {
                    // Attempt to handle a non-schema-match
                    $bol_schema_match = FALSE;
                    $obj_gds_entity = (new \GDS\Entity())->setKind($obj_path_end->kind);
                }
            } else {
                throw new \RuntimeException("No Kind for end(path) for Entity?");
            }

            // ID or Name
            if(isset($obj_path_end->id)) {
                $obj_gds_entity->setKeyId($obj_path_end->id);
            } elseif (isset($obj_path_end->name)) {
                $obj_gds_entity->setKeyName($obj_path_end->name);
            } else {
                throw new \RuntimeException("No KeyID or KeyName for Entity?");
            }

            // Ancestors?
            $int_path_elements = count($arr_path);
            if($int_path_elements > 0) {
                $arr_anc_path = [];
                foreach ($arr_path as $obj_kpe) {
                    $arr_anc_path[] = [
                        'kind' => $obj_kpe->kind,
                        'id' => isset($obj_kpe->id) ? $obj_kpe->id : null,
                        'name' => isset($obj_kpe->name) ? $obj_kpe->name : null
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
     * Extract a single property value from a Property object
     *
     * Defer any varying data type extractions to child classes
     *
     * @param $int_type
     * @param object $obj_property
     * @return mixed
     * @throws \Exception
     */
    protected function extractPropertyValue($int_type, $obj_property)
    {
        switch ($int_type) {
            case Schema::PROPERTY_STRING:
                return isset($obj_property->stringValue) ? $obj_property->stringValue : null;

            case Schema::PROPERTY_INTEGER:
                return isset($obj_property->integerValue) ? $obj_property->integerValue : null;

            case Schema::PROPERTY_DATETIME:
                return isset($obj_property->timestampValue) ? $this->extractDatetimeValue($obj_property) : null;

            case Schema::PROPERTY_DOUBLE:
            case Schema::PROPERTY_FLOAT:
                return isset($obj_property->doubleValue) ? $obj_property->doubleValue : null;

            case Schema::PROPERTY_BOOLEAN:
                return isset($obj_property->booleanValue) ? $obj_property->booleanValue : null;

            case Schema::PROPERTY_GEOPOINT:
                return isset($obj_property->geoPointValue) ? $this->extractGeopointValue($obj_property) : null;

            case Schema::PROPERTY_STRING_LIST:
                return $this->extractStringListValue($obj_property);

            case Schema::PROPERTY_DETECT:
                return $this->extractAutoDetectValue($obj_property);

        }
        throw new \Exception('Unsupported field type: ' . $int_type);
    }

    /**
     * Create a REST representation of a GDS entity
     *
     * https://cloud.google.com/datastore/reference/rest/v1/Entity
     *
     * @param Entity $obj_gds_entity
     * @return \stdClass
     */
    public function mapToGoogle(Entity $obj_gds_entity)
    {

        // Base entity with key (partition applied later)
        $obj_rest_entity = (object)[
            'key' => (object)['path' => $this->buildKeyPath($obj_gds_entity)],
            'properties' => (object)[]
        ];

        // Properties
        $arr_field_defs = $this->bestEffortFieldDefs($obj_gds_entity);
        foreach($obj_gds_entity->getData() as $str_field_name => $mix_value) {
            if(isset($arr_field_defs[$str_field_name])) {
                $obj_rest_entity->properties->{$str_field_name} = $this->createPropertyValue($arr_field_defs[$str_field_name], $mix_value);
            } else {
                $arr_dynamic_data = $this->determineDynamicType($mix_value);
                $obj_rest_entity->properties->{$str_field_name} = $this->createPropertyValue(['type' => $arr_dynamic_data['type'], 'index' => true], $arr_dynamic_data['value']);
            }
        }

        return $obj_rest_entity;
    }

    /**
     * Find and return the field definitions (if any) for the Entity
     *
     * @param Entity $obj_gds_entity
     * @return array
     */
    private function bestEffortFieldDefs(Entity $obj_gds_entity)
    {
        if($obj_gds_entity->getSchema() instanceof Schema) {
            return $obj_gds_entity->getSchema()->getProperties();
        }
        if($this->obj_schema instanceof Schema) {
            return $this->obj_schema->getProperties();
        }
        return [];
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
    public function buildKeyPath(Entity $obj_gds_entity, $bol_first_node = true)
    {
        $str_kind = $obj_gds_entity->getKind();
        if(null === $str_kind) {
            if($bol_first_node) {
                if($this->obj_schema instanceof Schema) {
                    $str_kind = $this->obj_schema->getKind();
                } else {
                    throw new \Exception('Could not build full key path, no Schema set on Mapper and no Kind set on Entity');
                }
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
            $arr_ancestor_path = [];
            foreach($mix_ancestry as $arr_ancestor_element) {
                $arr_ancestor_path[] = $this->createKeyPathElement($arr_ancestor_element);
            }
            $arr_full_path = array_merge($arr_ancestor_path, $arr_full_path);
        } elseif ($mix_ancestry instanceof Entity) {
            $arr_full_path = array_merge($this->buildKeyPath($mix_ancestry, false), $arr_full_path);
        }
        return $arr_full_path;
    }

    /**
     * Create a Key Path Element from array
     *
     * @param array $arr_kpe
     * @return \stdClass
     */
    protected function createKeyPathElement(array $arr_kpe)
    {
        $obj_element = (object)['kind' => $arr_kpe['kind']];
        if(isset($arr_kpe['id'])) {
            $obj_element->id = $arr_kpe['id'];
        } elseif (isset($arr_kpe['name'])) {
            $obj_element->name = $arr_kpe['name'];
        }
        return $obj_element;
    }

    /**
     * Create a property object
     *
     * @todo Compare with parameter value method from REST Gateway
     *
     * @param array $arr_field_def
     * @param $mix_value
     * @return mixed
     */
    protected function createPropertyValue(array $arr_field_def, $mix_value)
    {
        $obj_property_value = new \stdClass();

        // Indexed?
        $bol_index = TRUE;
        if(isset($arr_field_def['index']) && FALSE === $arr_field_def['index']) {
            $bol_index = FALSE;
        }
        $obj_property_value->excludeFromIndexes = !$bol_index;

        switch ($arr_field_def['type']) {
            case Schema::PROPERTY_STRING:
                $obj_property_value->stringValue = (string)$mix_value;
                break;

            case Schema::PROPERTY_INTEGER:
                $obj_property_value->integerValue = $mix_value;
                break;

            case Schema::PROPERTY_DATETIME:
                if($mix_value instanceof \DateTime) {
                    $obj_dtm = $mix_value;
                } else {
                    $obj_dtm = new \DateTime($mix_value);
                }
                // A timestamp in RFC3339 UTC "Zulu" format, accurate to nanoseconds. Example: "2014-10-02T15:01:23.045123456Z".
                $obj_property_value->timestampValue = $obj_dtm->format(self::DATETIME_FORMAT);
                break;

            case Schema::PROPERTY_DOUBLE:
            case Schema::PROPERTY_FLOAT:
                $obj_property_value->doubleValue = floatval($mix_value);
                break;

            case Schema::PROPERTY_BOOLEAN:
                $obj_property_value->booleanValue = (bool)$mix_value;
                break;

            case Schema::PROPERTY_GEOPOINT:
                if($mix_value instanceof Geopoint) {
                    /** @var Geopoint $mix_value */
                    $obj_property_value->geoPointValue = (object)[
                        "latitude" => $mix_value->getLatitude(),
                        "longitude" => $mix_value->getLongitude()
                    ];
                } elseif (is_array($mix_value)) {
                    $obj_property_value->geoPointValue = (object)[
                        "latitude" => $mix_value[0],
                        "longitude" => $mix_value[1]
                    ];
                } else {
                    throw new \InvalidArgumentException('Geopoint property data not supported: ' . gettype($mix_value));
                }
                break;

            case Schema::PROPERTY_STRING_LIST:
                // Docs: "A Value instance that sets field arrayValue must not set fields meaning or excludeFromIndexes."
                unset($obj_property_value->excludeFromIndexes);
                // As we cannot set excludeFromIndexes on the property itself, set it on each value in the array
                $arr_values = [];
                foreach ((array)$mix_value as $str) {
                    $obj_value = (object)['stringValue' => $str];
                    $obj_value->excludeFromIndexes = !$bol_index;
                    $arr_values[] = $obj_value;
                }
                $obj_property_value->arrayValue = (object)['values' => $arr_values];
                break;

            default:
                throw new \RuntimeException('Unable to process field type: ' . $arr_field_def['type']);
        }
        return $obj_property_value;
    }
}
