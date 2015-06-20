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
class CustomEntityClassTest extends GDSTest
{

    /**
     * Test setting a non-existent Entity class
     *
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Cannot set missing Entity class: DoesNotExist
     */
    public function testSetMissingClass()
    {
        $obj_gateway = new \GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $obj_store->setEntityClass('DoesNotExist');
    }

    /**
     * Test setting a non-Entity class
     *
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Cannot set an Entity class that does not extend "GDS\Entity": Simple
     */
    public function testSetInvalidClass()
    {
        $obj_gateway = new \GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $obj_store->setEntityClass('Simple');
    }

    /**
     * Set the Book custom entity class
     */
    public function testSetClass()
    {
        $obj_gateway = new \GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $obj_store->setEntityClass('Book');
    }

    /**
     * Set the Book custom entity class
     */
    public function testCreateEntity()
    {
        $obj_gateway = new \GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $obj_store->setEntityClass('Book');
        $obj_book = $obj_store->createEntity(['title' => 'Discworld']);
        $this->assertInstanceOf('\\Book', $obj_book);
    }

    /**
     * Fetch with custom entity class
     */
    public function testFetchByIdWithResult()
    {

        $obj_request = new \google\appengine\datastore\v4\LookupRequest();
        $obj_request->mutableReadOptions();
        $obj_key = $obj_request->addKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setId(123456789);

        $obj_response = new \google\appengine\datastore\v4\LookupResponse();
        $obj_found = $obj_response->addFound();
        $obj_entity = $obj_found->mutableEntity();
        $obj_result_key = $obj_entity->mutableKey();
        $obj_result_kpe = $obj_result_key->addPathElement();
        $obj_result_kpe->setKind('Book');
        $obj_result_kpe->setId(123456789);
        $obj_result_property = $obj_entity->addProperty();
        $obj_result_property->setName('author');
        $obj_val = $obj_result_property->mutableValue(); // addDeprecatedValue();
        $obj_val->setStringValue('William Shakespeare');

        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $obj_request, $obj_response);

        $obj_gateway = new \GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $obj_store->setEntityClass('Book');
        $obj_result = $obj_store->fetchById(123456789);

        $this->assertInstanceOf('\\GDS\\Entity', $obj_result);
        $this->assertInstanceOf('\\Book', $obj_result);
        $this->assertEquals(1, count($obj_result->getData()));
        $this->assertEquals('Book', $obj_result->getKind());
        $this->assertEquals(123456789, $obj_result->getKeyId());
        $this->assertEquals($obj_result->author, 'William Shakespeare');

        $this->apiProxyMock->verify();
    }


    // @todo test with Schema

}