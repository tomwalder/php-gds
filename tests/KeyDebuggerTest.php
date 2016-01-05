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
 * Tests for KeyDebugger class
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class KeyDebuggerTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     */
    public function testSingleId()
    {
        $obj_key1 = new GDS\Key();
        $obj_key1->setKind('Single')->setKeyId(123456);

        $obj_debugger = new \GDS\KeyDebugger();
        $this->assertEquals(
            '[(Kind:Single, Id:123456)]',
            $obj_debugger->renderKeyChain($obj_key1)
        );
    }

    /**
     *
     */
    public function testSingleName()
    {
        $obj_key1 = new GDS\Key();
        $obj_key1->setKind('Single')->setKeyName('onetwothree');

        $obj_debugger = new \GDS\KeyDebugger();
        $this->assertEquals(
            '[(Kind:Single, Name:onetwothree)]',
            $obj_debugger->renderKeyChain($obj_key1)
        );
    }

    /**
     *
     */
    public function testParentChild()
    {
        $obj_key1 = new GDS\Key();
        $obj_key1->setKind('Parent')->setKeyId(123456);
        $obj_key2 = new GDS\Key();
        $obj_key2->setKind('Child')->setKeyName('Quay');
        $obj_key2->setAncestry($obj_key1);

        $obj_debugger = new \GDS\KeyDebugger();
        $this->assertEquals(
            '[(Kind:Parent, Id:123456)->(Kind:Child, Name:Quay)]',
            $obj_debugger->renderKeyChain($obj_key2)
        );
    }

    /**
     *
     */
    public function testGrandParentChild()
    {
        $obj_key0 = new GDS\Key();
        $obj_key0->setKind('GrandParent')->setKeyId(98765);
        $obj_key1 = new GDS\Key();
        $obj_key1->setKind('Parent')->setKeyId(123456);
        $obj_key1->setAncestry($obj_key0);
        $obj_key2 = new GDS\Key();
        $obj_key2->setKind('Child')->setKeyName('Quay');
        $obj_key2->setAncestry($obj_key1);

        $obj_debugger = new \GDS\KeyDebugger();
        $this->assertEquals(
            '[(Kind:GrandParent, Id:98765)->(Kind:Parent, Id:123456)->(Kind:Child, Name:Quay)]',
            $obj_debugger->renderKeyChain($obj_key2)
        );
    }

    /**
     *
     */
    public function testArray()
    {
        $obj_key0 = new GDS\Key();
        $obj_key0->setKind('GrandParent')->setKeyId(98765);
        $obj_key1 = new GDS\Key();
        $obj_key1->setKind('Parent')->setKeyId(123456);

        $obj_key2 = new GDS\Key();
        $obj_key2->setKind('Child')->setKeyName('Quay');
        $obj_key2->setAncestry([$obj_key0, $obj_key1]);

        $obj_debugger = new \GDS\KeyDebugger();
        $this->assertEquals(
            '[(Kind:GrandParent, Id:98765)->(Kind:Parent, Id:123456)->(Kind:Child, Name:Quay)]',
            $obj_debugger->renderKeyChain($obj_key2)
        );
    }

    /**
     *
     */
    public function testEntity()
    {
        $obj_ent = new \GDS\Entity();
        $obj_ent->setKind('Single')->setKeyId(123456);
        $obj_ent->name = 'Tom';

        $obj_debugger = new \GDS\KeyDebugger();
        $this->assertEquals(
            '[(Kind:Single, Id:123456)]',
            $obj_debugger->renderKeyChain($obj_ent)
        );
    }

}