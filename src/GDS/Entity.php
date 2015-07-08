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
 * @todo Consider providing a __construct('Kind')
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 */
class Entity
{

    /**
     * Datastore entity Kind
     *
     * @var string|null
     */
    private $str_kind = null;

    /**
     * GDS record Key ID
     *
     * @var string
     */
    private $str_key_id = null;

    /**
     * GDS record Key Name
     *
     * @var string
     */
    private $str_key_name = null;

    /**
     * Entity ancestors
     *
     * @var null|array|Entity
     */
    private $mix_ancestry = null;

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
     * Get the Entity Kind
     *
     * @return null
     */
    public function getKind()
    {
        return $this->str_kind;
    }

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
     * @param $str_kind
     * @return $this
     */
    public function setKind($str_kind)
    {
        $this->str_kind = $str_kind;
        return $this;
    }

    /**
     * Set the key ID
     *
     * @param $str_key_id
     * @return $this
     */
    public function setKeyId($str_key_id)
    {
        $this->str_key_id = $str_key_id;
        return $this;
    }

    /**
     * Set the key name
     *
     * @param $str_key_name
     * @return $this
     */
    public function setKeyName($str_key_name)
    {
        $this->str_key_name = $str_key_name;
        return $this;
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
     * Set the Entity's ancestry. This either an array of paths OR another Entity
     *
     * @param $mix_path
     * @return $this
     */
    public function setAncestry($mix_path)
    {
        $this->mix_ancestry = $mix_path;
        return $this;
    }

    /**
     * Get the ancestry of the entity
     *
     * @return null|array
     */
    public function getAncestry()
    {
        return $this->mix_ancestry;
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