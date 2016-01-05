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
 * Tests for Key class
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class KeyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Check our Kind and KeyId/KeyName setters, getters
     */
    public function testBasics()
    {
        $obj_key1 = new GDS\Key();
        $obj_key1->setKind('Testing')->setKeyId(123456);
        $this->assertEquals('Testing', $obj_key1->getKind());
        $this->assertEquals(123456, $obj_key1->getKeyId());

        $obj_key2 = new GDS\Key();
        $obj_key2->setKind('Testing')->setKeyName('Quay');
        $this->assertEquals('Testing', $obj_key2->getKind());
        $this->assertEquals('Quay', $obj_key2->getKeyName());
    }

    /**
     * Check our Ancestry setters, getters
     */
    public function testAncestry()
    {
        $obj_key1 = new GDS\Key();
        $obj_key1->setAncestry(['path']);
        $this->assertEquals(['path'], $obj_key1->getAncestry());

        $obj_key2 = new GDS\Key();
        $obj_ancestor = new GDS\Key();
        $obj_key2->setAncestry($obj_ancestor);
        $this->assertEquals($obj_ancestor, $obj_key2->getAncestry());

        $obj_key3 = new GDS\Key();
        $obj_entity = new GDS\Entity();
        $obj_key3->setAncestry($obj_entity);
        $this->assertEquals($obj_entity, $obj_key3->getAncestry());
    }

    /**
     * Check our Ancestry setters, getters for failure
     *
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Supplied ancestry must be an Array or instance of KeyInterface. Supplied: string
     */
    public function testAncestryFail()
    {
        $obj_key1 = new GDS\Key();
        $obj_key1->setAncestry('string');
    }

}