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
 * Tests for Entity class
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class EntityTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Check our Kind and KeyId/KeyName setters, getters
     */
    public function testBasics()
    {
        $obj_entity1 = new GDS\Entity();
        $obj_entity1->setKind('Testing')->setKeyId(123456);
        $this->assertEquals($obj_entity1->getKind(), 'Testing');
        $this->assertEquals($obj_entity1->getKeyId(), 123456);

        $obj_entity2 = new GDS\Entity();
        $obj_entity2->setKind('Testing')->setKeyName('testing');
        $this->assertEquals($obj_entity2->getKind(), 'Testing');
        $this->assertEquals($obj_entity2->getKeyName(), 'testing');
    }

    /**
     * Ensure a Schema when applied sets the Kind
     */
    public function testBasicSchema()
    {
        $obj_schema = new \GDS\Schema('SomeKindOfTest');
        $obj_entity = new GDS\Entity();
        $obj_entity->setSchema($obj_schema);
        $this->assertEquals($obj_entity->getKind(), 'SomeKindOfTest');
        $this->assertEquals($obj_entity->getSchema(), $obj_schema);
    }

    /**
     * Ensure parameters can be set, detected and retrieved
     */
    public function testIsset()
    {
        $obj_entity = new GDS\Entity();
        $obj_entity->setSchema(new \GDS\Schema('SomeKindOfTest'));
        $obj_entity->test_property = 'Has value';
        $this->assertTrue(isset($obj_entity->test_property));
        $this->assertFalse(isset($obj_entity->another_property));
        $this->assertEquals($obj_entity->test_property, 'Has value');
        $this->assertEquals($obj_entity->another_property, null);
    }

    /**
     * Ensure parameters can be retrieved en masse
     */
    public function testGetData()
    {
        $obj_entity = new GDS\Entity();
        $obj_entity->setSchema(new \GDS\Schema('SomeKindOfTest'));
        $obj_entity->test_property = 'Has value';
        $obj_entity->another_property = 'Another value';
        $this->assertEquals($obj_entity->getData(), [
            'test_property' => 'Has value',
            'another_property' => 'Another value'
        ]);
    }

    /**
     * Validate getters and setters for Ancestry
     */
    public function testAncestry()
    {
        $obj_entity1 = new GDS\Entity();
        $obj_entity1->setKind('Testing')->setKeyId(123456);

        $obj_entity2 = new GDS\Entity();
        $obj_entity2->setKind('Testing')->setKeyName('testing');
        $obj_entity2->setAncestry($obj_entity1);

        $this->assertEquals($obj_entity2->getAncestry(), $obj_entity1);
    }
}