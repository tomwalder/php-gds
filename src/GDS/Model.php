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
 * GDS Model
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 */
abstract class Model
{

    /**
     * GDS record Key ID
     *
     * @var string
     */
    private $str_key_id = NULL;

    /**
     * GDS record Key Name
     *
     * @var string
     */
    private $str_key_name = NULL;

    /**
     * Field Data
     *
     * @var array
     */
    private $arr_data = [];

    /**
     * Get the key ID
     *
     * @return string
     */
    public function getKeyId()
    {
        return $this->str_key_id;
    }

    /**
     * Get the key name
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->str_key_name;
    }

    /**
     * Set the key ID
     *
     * @param $str_key_id
     */
    public function setKeyId($str_key_id)
    {
        $this->str_key_id = $str_key_id;
    }

    /**
     * Set the key name
     *
     * @param $str_key_name
     */
    public function setKeyName($str_key_name)
    {
        $this->str_key_name = $str_key_name;
    }

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
        return NULL;
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
     * Does this Model instance contain data for the supplied field? Or any data if no field specified?
     *
     * @param $str_key
     * @return bool
     */
    public function hasData($str_key = NULL)
    {
        if(NULL === $str_key) {
            return !empty($this->arr_data);
        }
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



}