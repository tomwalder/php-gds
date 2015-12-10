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
 * GDS Entity
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 */
class Entity extends Key implements KeyInterface
{

    /**
     * Field Data
     *
     * @var array
     */
    private $arr_data = [];

    /**
     * The Schema for the Entity, if known.
     *
     * @var Schema|null
     */
    private $obj_schema = null;

    /**
     * Magic setter.. sorry
     *
     * @param $str_key
     * @param $mix_value
     */
    public function __set($str_key, $mix_value)
    {
        $this->arr_data[$str_key] = $mix_value;
    }

    /**
     * Magic getter.. sorry
     *
     * @param $str_key
     * @return null
     */
    public function __get($str_key)
    {
        if(isset($this->arr_data[$str_key])) {
            return $this->arr_data[$str_key];
        }
        return null;
    }

    /**
     * Is a data value set?
     *
     * @param $str_key
     * @return bool
     */
    public function __isset($str_key)
    {
        return isset($this->arr_data[$str_key]);
    }

    /**
     * Get the entire data array
     *
     * @return array
     */
    public function getData()
    {
        return $this->arr_data;
    }

    /**
     * The Schema for the Entity, if known.
     *
     * @return Schema|null
     */
    public function getSchema()
    {
        return $this->obj_schema;
    }

    /**
     * Set the Schema for the Entity
     *
     * @param Schema $obj_schema
     * @return $this
     */
    public function setSchema(Schema $obj_schema)
    {
        $this->obj_schema = $obj_schema;
        $this->setKind($obj_schema->getKind());
        return $this;
    }

}