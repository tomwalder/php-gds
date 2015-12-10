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
 * GDS Key Interface
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 */
interface KeyInterface
{

    /**
     * Get the Entity Kind
     *
     * @return null
     */
    public function getKind();

    /**
     * Get the key ID
     *
     * @return string
     */
    public function getKeyId();

    /**
     * Get the key name
     *
     * @return string
     */
    public function getKeyName();

    /**
     * Get the ancestry of the entity
     *
     * @return null|array|KeyInterface
     */
    public function getAncestry();

    /**
     * Set the Entity Kind
     *
     * @param $str_kind
     * @return $this
     */
    public function setKind($str_kind);

    /**
     * Set the key ID
     *
     * @param $str_key_id
     * @return $this
     */
    public function setKeyId($str_key_id);

    /**
     * Set the key name
     *
     * @param $str_key_name
     * @return $this
     */
    public function setKeyName($str_key_name);

    /**
     * Set the Key's ancestry. This either an array of paths OR another KeyInterface
     *
     * @param $mix_path
     * @return $this
     */
    public function setAncestry($mix_path);

}