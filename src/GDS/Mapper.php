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
     * Datetime format for backwards compatibility
     */
    const DATETIME_FORMAT_V2 = 'Y-m-d H:i:s';

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
     * @return mixed
     * @throws \Exception
     */
    abstract protected function extractPropertyValue($int_type, $obj_property);

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