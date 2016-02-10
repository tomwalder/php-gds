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
use GDS\Property\Geopoint;

/**
 * Map between Google Entity and GDS Entity data/objects
 *
 * @author Tom Walder <tom@docnet.nu>
 */
abstract class Mapper
{

    /**
     * Current Schema
     *
     * @var Schema
     */
    protected $obj_schema = null;

    /**
     * Set the schema
     *
     * @param Schema $obj_schema
     * @return $this
     */
    public function setSchema(Schema $obj_schema)
    {
        $this->obj_schema = $obj_schema;
        return $this;
    }

    /**
     * Dynamically determine type for a value
     *
     * @param $mix_value
     * @return array
     */
    protected function determineDynamicType($mix_value)
    {
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
                if($mix_value instanceof Geopoint) {
                    $int_dynamic_type = Schema::PROPERTY_GEOPOINT;
                    break;
                }
                $int_dynamic_type = Schema::PROPERTY_STRING;
                if(method_exists($mix_value, '__toString')) {
                    $mix_value = $mix_value->__toString();
                } else {
                    $mix_value = null;
                }
                break;

            case 'resource':
            case 'null':
            case 'unknown type':
            default:
                $int_dynamic_type = Schema::PROPERTY_STRING;
                $mix_value = null;
        }
        return [
            'type' => $int_dynamic_type,
            'value' => $mix_value
        ];
    }

    /**
     * Map 1-many results out of the Raw response data array
     *
     * @param array $arr_results
     * @return Entity[]|null
     */
    public function mapFromResults(array $arr_results)
    {
        $arr_entities = [];
        foreach ($arr_results as $obj_result) {
            $arr_entities[] = $this->mapOneFromResult($obj_result);
        }
        return $arr_entities;
    }

    /**
     * Extract a single property value from a Property object
     *
     * Defer any varying data type extractions to child classes
     *
     * @param $int_type
     * @param object $obj_property
     * @return array
     * @throws \Exception
     */
    protected function extractPropertyValue($int_type, $obj_property)
    {
        switch ($int_type) {
            case Schema::PROPERTY_STRING:
                return $obj_property->getStringValue();

            case Schema::PROPERTY_INTEGER:
                return $obj_property->getIntegerValue();

            case Schema::PROPERTY_DATETIME:
                return $this->extractDatetimeValue($obj_property);

            case Schema::PROPERTY_DOUBLE:
            case Schema::PROPERTY_FLOAT:
                return $obj_property->getDoubleValue();

            case Schema::PROPERTY_BOOLEAN:
                return $obj_property->getBooleanValue();

            case Schema::PROPERTY_GEOPOINT:
                return $this->extractGeopointValue($obj_property);

            case Schema::PROPERTY_STRING_LIST:
                return $this->extractStringListValue($obj_property);

            case Schema::PROPERTY_DETECT:
                return $this->extractAutoDetectValue($obj_property);

        }
        throw new \Exception('Unsupported field type: ' . $int_type);
    }

    /**
     * Auto detect & extract a value
     *
     * @param object $obj_property
     * @return mixed
     */
    abstract protected function extractAutoDetectValue($obj_property);

    /**
     * Extract a datetime value
     *
     * @param $obj_property
     * @return mixed
     */
    abstract protected function extractDatetimeValue($obj_property);

    /**
     * Extract a String List value
     *
     * @param $obj_property
     * @return mixed
     */
    abstract protected function extractStringListValue($obj_property);

    /**
     * Extract a Geopoint value
     *
     * @param $obj_property
     * @return Geopoint
     */
    abstract protected function extractGeopointValue($obj_property);

    /**
     * Map a single result out of the Raw response data array FROM Google TO a GDS Entity
     *
     * @param object $obj_result
     * @return Entity
     * @throws \Exception
     */
    abstract public function mapOneFromResult($obj_result);

}