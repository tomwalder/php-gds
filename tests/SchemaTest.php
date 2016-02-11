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

/**
 * Tests for Schema class
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class SchemaTest extends \PHPUnit_Framework_TestCase {

    /**
     * Set up a schema with all data types
     */
    public function testSchema()
    {
        $obj_schema = (new \GDS\Schema('Person'))
            ->addString('name')
            ->addInteger('age')
            ->addDatetime('dob')
            ->addBoolean('single')
            ->addFloat('weight')
            ->addStringList('nicknames')
            ->addProperty('surname', \GDS\Schema::PROPERTY_STRING)
            ->addInteger('friends', FALSE)
            ->addGeopoint('location')
        ;
        $this->assertEquals($obj_schema->getKind(), 'Person');
        $this->assertEquals($obj_schema->getProperties(), [
            'name' => [
                'type' => \GDS\Schema::PROPERTY_STRING,
                'index' => TRUE
            ],
            'age' => [
                'type' => \GDS\Schema::PROPERTY_INTEGER,
                'index' => TRUE
            ],
            'dob' => [
                'type' => \GDS\Schema::PROPERTY_DATETIME,
                'index' => TRUE
            ],
            'single' => [
                'type' => \GDS\Schema::PROPERTY_BOOLEAN,
                'index' => TRUE
            ],
            'weight' => [
                'type' => \GDS\Schema::PROPERTY_FLOAT,
                'index' => TRUE
            ],
            'nicknames' => [
                'type' => \GDS\Schema::PROPERTY_STRING_LIST,
                'index' => TRUE
            ],
            'surname' => [
                'type' => \GDS\Schema::PROPERTY_STRING,
                'index' => TRUE
            ],
            'friends' => [
                'type' => \GDS\Schema::PROPERTY_INTEGER,
                'index' => FALSE
            ],
            'location' => [
                'type' => \GDS\Schema::PROPERTY_GEOPOINT,
                'index' => TRUE
            ]
        ]);
    }

}