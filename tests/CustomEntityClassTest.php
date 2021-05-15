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
 * @author Tom Walder <twalder@gmail.com>
 */
class CustomEntityClassTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Test setting a non-existent Entity class
     */
    public function testSetMissingClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot set missing Entity class: DoesNotExist');
        $obj_gateway = new \GDS\Gateway\RESTv1('Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $obj_store->setEntityClass('DoesNotExist');
    }

    /**
     * Test setting a non-Entity class
     */
    public function testSetInvalidClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot set an Entity class that does not extend "GDS\Entity": Simple');
        $obj_gateway = new \GDS\Gateway\RESTv1('Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $obj_store->setEntityClass('Simple');
    }

    /**
     * Set the Book custom entity class
     */
    public function testSetClass()
    {
        $obj_gateway = new \GDS\Gateway\RESTv1('Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $obj_store2 = $obj_store->setEntityClass('Book');
        $this->assertSame($obj_store, $obj_store2);
    }

    /**
     * Set the Book custom entity class
     */
    public function testCreateEntity()
    {
        $obj_gateway = new \GDS\Gateway\RESTv1('Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $obj_store->setEntityClass('Book');
        $obj_book = $obj_store->createEntity(['title' => 'Discworld']);
        $this->assertInstanceOf('\\Book', $obj_book);
    }

    // @todo test with Schema

}