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
 * GDS Model Schema
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 */
class Schema
{

    /**
     * Field data types
     */
    const FIELD_STRING = 1;
    const FIELD_INTEGER = 2;
    const FIELD_DATETIME = 3;
    const FIELD_DOUBLE = 4;
    const FIELD_FLOAT = 4; // FLOAT === DOUBLE
    const FIELD_BOOLEAN = 10; // 10 types of people...
    const FIELD_STRING_LIST = 20;
    const FIELD_DETECT = 99; // used for auto-detection

    /**
     * Kind (like database 'Table')
     *
     * @var string|null
     */
    private $str_kind = NULL;

    /**
     * Known fields
     *
     * @var array
     */
    private $arr_fields = [];

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
    public function addField($str_name, $int_type = self::FIELD_STRING, $bol_index = FALSE)
    {
        $this->arr_fields[$str_name] = [
            'type' => $int_type,
            'index' => $bol_index
        ];
        return $this;
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
    public function getFields()
    {
        return $this->arr_fields;
    }

}