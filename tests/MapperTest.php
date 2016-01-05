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
 * Tests for Mapper class
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class MapperTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Check for failure
     *
     * @expectedException           Exception
     * @expectedExceptionMessage    Unsupported field type: 999
     */
    public function testUnsupportedFields()
    {
        $obj_schema = new \GDS\Schema('Test');
        $obj_schema->addProperty('unknown', 999);

        $obj_entity_result = new google\appengine\datastore\v4\EntityResult();
        $obj_entity = $obj_entity_result->mutableEntity();
        $obj_entity->mutableKey()->addPathElement()->setKind('Test')->setName('KeyName');
        $obj_entity->addProperty()->setName('unknown')->mutableValue()->setStringValue('I am really a string');

        $obj_mapper = new \GDS\Mapper\ProtoBuf();
        $obj_mapper->setSchema($obj_schema);
        $obj_mapper->mapOneFromResult($obj_entity_result);
    }

    /**
     * Check for failure
     *
     * @expectedException           Exception
     * @expectedExceptionMessage    Unable to process field type: 999
     */
    public function testUnsupportedProperty()
    {
        $obj_schema = new \GDS\Schema('Test');
        $obj_schema->addProperty('unknown', 999);

        $obj_entity = new \GDS\Entity();
        $obj_entity->unknown = 'I am really a string';

        $obj_mapper = new \GDS\Mapper\GoogleAPIClient();
        $obj_mapper->setSchema($obj_schema);
        $obj_mapper->mapToGoogle($obj_entity);
    }

}