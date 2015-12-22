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
namespace GDS;

/**
 * GDS Entity Key
 *
 * GDS Keys are [kind + id/name]
 * GDS Keys can have ancestors (another GDS KeyInterface)
 *
 * Key > Key > Key : This is a "Key chain" /fnar
 *
 * @todo Consider partition (dataset + namespace)
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 */
class Key implements KeyInterface
{

    /**
     * Datastore entity Kind
     *
     * @var string|null
     */
    protected $str_kind = null;

    /**
     * Key ID (64 bit integer, modelled as string)
     *
     * @var string
     */
    protected $str_key_id = null;

    /**
     * Key Name (string, 500 chars max)
     *
     * @var string
     */
    protected $str_key_name = null;

    /**
     * Entity ancestors
     *
     * @var null|array|KeyInterface
     */
    protected $mix_ancestry = null;

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
     * Set the Entity Kind
     *
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
     * Set the Entity's ancestry. This either an array of paths OR another KeyInterface
     *
     * @todo Add validation for supported types of ancestry
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
     * @return null|array|KeyInterface
     */
    public function getAncestry()
    {
        return $this->mix_ancestry;
    }

}