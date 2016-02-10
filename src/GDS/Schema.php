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
 * GDS Entity Schema
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 */
class Schema
{

    /**
     * Field data types
     */
    const PROPERTY_STRING = 1;
    const PROPERTY_INTEGER = 2;
    const PROPERTY_DATETIME = 3;
    const PROPERTY_DOUBLE = 4;
    const PROPERTY_FLOAT = 4; // FLOAT === DOUBLE
    const PROPERTY_BLOB = 5;
    const PROPERTY_GEOPOINT = 6;
    const PROPERTY_BOOLEAN = 10; // 10 types of people...
    const PROPERTY_STRING_LIST = 20;
    const PROPERTY_INTEGER_LIST = 21;
    const PROPERTY_ENTITY = 30;
    const PROPERTY_KEY = 40;
    const PROPERTY_DETECT = 99; // used for auto-detection

    /**
     * Kind (like database 'Table')
     *
     * @var string|null
     */
    private $str_kind = null;

    /**
     * Known fields
     *
     * @var array
     */
    private $arr_defined_properties = [];

    /**
     * The class to use when instantiating new Entity objects
     *
     * @var string
     */
    private $str_entity_class = '\\GDS\\Entity';

    /**
     * Kind is required
     *
     * @param $str_kind
     */
    public function __construct($str_kind)
    {
        $this->str_kind = $str_kind;
    }

    /**
     * Add a field to the known field array
     *
     * @param $str_name
     * @param $int_type
     * @param bool $bol_index
     * @return $this
     */
    public function addProperty($str_name, $int_type = self::PROPERTY_STRING, $bol_index = TRUE)
    {
        $this->arr_defined_properties[$str_name] = [
            'type' => $int_type,
            'index' => $bol_index
        ];
        return $this;
    }

    /**
     * Add a string field to the schema
     *
     * @param $str_name
     * @param bool $bol_index
     * @return Schema
     */
    public function addString($str_name, $bol_index = TRUE)
    {
        return $this->addProperty($str_name, self::PROPERTY_STRING, $bol_index);
    }

    /**
     * Add an integer field to the schema
     *
     * @param $str_name
     * @param bool $bol_index
     * @return Schema
     */
    public function addInteger($str_name, $bol_index = TRUE)
    {
        return $this->addProperty($str_name, self::PROPERTY_INTEGER, $bol_index);
    }

    /**
     * Add a datetime field to the schema
     *
     * @param $str_name
     * @param bool $bol_index
     * @return Schema
     */
    public function addDatetime($str_name, $bol_index = TRUE)
    {
        return $this->addProperty($str_name, self::PROPERTY_DATETIME, $bol_index);
    }

    /**
     * Add a float|double field to the schema
     *
     * @param $str_name
     * @param bool $bol_index
     * @return Schema
     */
    public function addFloat($str_name, $bol_index = TRUE)
    {
        return $this->addProperty($str_name, self::PROPERTY_FLOAT, $bol_index);
    }

    /**
     * Add a boolean field to the schema
     *
     * @param $str_name
     * @param bool $bol_index
     * @return Schema
     */
    public function addBoolean($str_name, $bol_index = TRUE)
    {
        return $this->addProperty($str_name, self::PROPERTY_BOOLEAN, $bol_index);
    }

    /**
     * Add a geopoint field to the schema
     *
     * @param $str_name
     * @param bool $bol_index
     * @return Schema
     */
    public function addGeopoint($str_name, $bol_index = TRUE)
    {
        return $this->addProperty($str_name, self::PROPERTY_GEOPOINT, $bol_index);
    }

    /**
     * Add a string-list (array of strings) field to the schema
     *
     * @param $str_name
     * @param bool $bol_index
     * @return Schema
     */
    public function addStringList($str_name, $bol_index = TRUE)
    {
        return $this->addProperty($str_name, self::PROPERTY_STRING_LIST, $bol_index);
    }

    /**
     * Get the Kind
     *
     * @return string
     */
    public function getKind()
    {
        return $this->str_kind;
    }

    /**
     * Get the configured fields
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->arr_defined_properties;
    }

    /**
     * Set the class to use when instantiating new Entity objects
     *
     * Must be GDS\Entity, or a sub-class of it
     *
     * @param $str_class
     * @return $this
     * @throws \InvalidArgumentException
     */
    public final function setEntityClass($str_class)
    {
        if(class_exists($str_class)) {
            if(is_a($str_class, '\\GDS\\Entity', TRUE)) {
                $this->str_entity_class = $str_class;
            } else {
                throw new \InvalidArgumentException('Cannot set an Entity class that does not extend "GDS\Entity": ' . $str_class);
            }
        } else {
            throw new \InvalidArgumentException('Cannot set missing Entity class: ' . $str_class);
        }
        return $this;
    }

    /**
     * Create a new instance of this GDS Entity class
     *
     * @return Entity
     */
    public final function createEntity()
    {
        return (new $this->str_entity_class())->setSchema($this);
    }

}