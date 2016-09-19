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
 * Tests for Protocol Buffer Fetching
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class ProtoBufFetchTest extends GDSTest {

    /**
     * Fetch by Name
     */
    public function testFetchByName()
    {
        $obj_request = new \google\appengine\datastore\v4\LookupRequest();
        $obj_request->mutableReadOptions();
        $obj_key = $obj_request->addKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setName('Romeo');

        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $obj_request, new \google\appengine\datastore\v4\LookupResponse());

        $obj_result = $this->createBasicStore()->fetchByName('Romeo');
        $this->assertEquals($obj_result, null);

        $this->apiProxyMock->verify();
    }

    /**
     * Fetch by Names
     */
    public function testFetchByNames()
    {
        $obj_request = new \google\appengine\datastore\v4\LookupRequest();
        $obj_request->mutableReadOptions();

        $obj_key = $obj_request->addKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setName('Romeo');

        $obj_key = $obj_request->addKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setName('Juliet');

        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $obj_request, new \google\appengine\datastore\v4\LookupResponse());

        $obj_result = $this->createBasicStore()->fetchByNames(['Romeo', 'Juliet']);
        $this->assertEquals($obj_result, []);

        $this->apiProxyMock->verify();
    }

    /**
     * Create a basic "fetch by ID" request for re-use
     *
     * @return \google\appengine\datastore\v4\LookupRequest
     */
    private function getBasicBookByIdRequest()
    {
        $obj_request = new \google\appengine\datastore\v4\LookupRequest();
        $obj_request->mutableReadOptions();
        $obj_key = $obj_request->addKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setId(123456789);
        return $obj_request;
    }

    /**
     * Create a basic "fetch by ID" request for re-use
     *
     * @return \google\appengine\datastore\v4\LookupRequest
     */
    private function getBasicPersonByIdRequest()
    {
        $obj_request = new \google\appengine\datastore\v4\LookupRequest();
        $obj_request->mutableReadOptions();
        $obj_key = $obj_request->addKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Person');
        $obj_kpe->setId(123456789);
        return $obj_request;
    }

    /**
     * Build and return a person response for re-use in multiple tests
     *
     * @return \google\appengine\datastore\v4\LookupResponse
     */
    private function getPersonResponse()
    {
        $obj_response = new \google\appengine\datastore\v4\LookupResponse();
        $obj_found = $obj_response->addFound();
        $obj_entity = $obj_found->mutableEntity();
        $obj_result_key = $obj_entity->mutableKey();
        $obj_result_kpe = $obj_result_key->addPathElement();
        $obj_result_kpe->setKind('Person');
        $obj_result_kpe->setId(123456789);
        $obj_entity->addProperty()->setName('name')->mutableValue()->setIndexed(TRUE)->setStringValue('Tom');
        $obj_entity->addProperty()->setName('age')->mutableValue()->setIndexed(TRUE)->setIntegerValue(36);
        $obj_entity->addProperty()->setName('dob')->mutableValue()->setIndexed(TRUE)->setTimestampMicrosecondsValue(286965000000000);
        $obj_entity->addProperty()->setName('weight')->mutableValue()->setIndexed(TRUE)->setDoubleValue(94.50);
        $obj_entity->addProperty()->setName('likes_php')->mutableValue()->setIndexed(TRUE)->setBooleanValue(TRUE);
        $obj_entity->addProperty()->setName('home')->mutableValue()->setIndexed(TRUE)->mutableGeoPointValue()->setLatitude(1.23)->setLongitude(4.56);
        return $obj_response;
    }

    /**
     * Fetch by Id
     */
    public function testFetchById()
    {
        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $this->getBasicBookByIdRequest(), new \google\appengine\datastore\v4\LookupResponse());
        $obj_result = $this->createBasicStore()->fetchById(123456789);
        $this->assertEquals($obj_result, null);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch by Id and process basic result
     */
    public function testFetchByIdWithResult()
    {

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

        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $this->getBasicBookByIdRequest(), $obj_response);

        $obj_result = $this->createBasicStore()->fetchById(123456789);

        $this->assertInstanceOf('\\GDS\\Entity', $obj_result);
        $this->assertEquals(1, count($obj_result->getData()));
        $this->assertEquals('Book', $obj_result->getKind());
        $this->assertEquals(123456789, $obj_result->getKeyId());
        $this->assertEquals($obj_result->author, 'William Shakespeare');

        $this->apiProxyMock->verify();
    }

    /**
     * Fetch by Id and process result with ancestors
     */
    public function testFetchByIdWithAncestorResult()
    {

        $obj_response = new \google\appengine\datastore\v4\LookupResponse();
        $obj_found = $obj_response->addFound();
        $obj_entity = $obj_found->mutableEntity();
        $obj_result_key = $obj_entity->mutableKey();
        $obj_result_kpe = $obj_result_key->addPathElement();
        $obj_result_kpe->setKind('Author');
        $obj_result_kpe->setName('WilliamShakespeare');

        $obj_result_kpe = $obj_result_key->addPathElement();
        $obj_result_kpe->setKind('Book');
        $obj_result_kpe->setId(123456789);
        $obj_result_property = $obj_entity->addProperty();
        $obj_result_property->setName('title');
        $obj_val = $obj_result_property->mutableValue(); // addDeprecatedValue();
        $obj_val->setStringValue('Romeo and Juliet');

        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $this->getBasicBookByIdRequest(), $obj_response);

        $obj_result = $this->createBasicStore()->fetchById(123456789);

        $this->assertInstanceOf('\\GDS\\Entity', $obj_result);
        $this->assertEquals(1, count($obj_result->getData()));
        $this->assertEquals('Book', $obj_result->getKind());
        $this->assertEquals(123456789, $obj_result->getKeyId());
        $this->assertEquals($obj_result->title, 'Romeo and Juliet');
        $this->assertEquals($obj_result->getAncestry(), [[
            'kind' => 'Author',
            'id' => null,
            'name' => 'WilliamShakespeare',
        ]]);

        $this->apiProxyMock->verify();
    }

    /**
     * Fetch by Ids
     */
    public function testFetchByIds()
    {
        $obj_request = new \google\appengine\datastore\v4\LookupRequest();
        $obj_request->mutableReadOptions();

        $obj_key = $obj_request->addKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setId(123456789);

        $obj_key = $obj_request->addKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setId(123456790);

        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $obj_request, new \google\appengine\datastore\v4\LookupResponse());

        $obj_result = $this->createBasicStore()->fetchByIds([123456789, 123456790]);
        $this->assertEquals($obj_result, []);

        $this->apiProxyMock->verify();
    }

    /**
     * Fetch with no schema for all supported property types
     *
     * @todo consider DateTime return type... string or DateTime object?
     */
    public function testFetchByIdWithVariantDataTypeResult()
    {
        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $this->getBasicPersonByIdRequest(), $this->getPersonResponse());
        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Person', $obj_gateway);
        $obj_result = $obj_store->fetchById(123456789);
        $this->assertInstanceOf('\\GDS\\Entity', $obj_result);
        $this->assertEquals($obj_result->getData(), [
            'name' => 'Tom',
            'age' => 36,
            'dob' => '1979-02-04 08:30:00',
            'weight' => 94.50,
            'likes_php' => TRUE,
            'home' => new \GDS\Property\Geopoint(1.23, 4.56)
        ]);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch with Schema for all supported property types
     */
    public function testFetchByIdWithVariantSchemaResult()
    {
        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $this->getBasicPersonByIdRequest(), $this->getPersonResponse());
        $obj_schema = (new \GDS\Schema('Person'))
            ->addString('name')
            ->addInteger('age')
            ->addDatetime('dob')
            ->addFloat('weight')
            ->addBoolean('likes_php')
            ->addGeopoint('home')
            // ->addStringList('nicknames')
        ;
        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store($obj_schema, $obj_gateway);
        $obj_result = $obj_store->fetchById(123456789);
        $this->assertInstanceOf('\\GDS\\Entity', $obj_result);
        $this->assertEquals($obj_result->getData(), [
            'name' => 'Tom',
            'age' => 36,
            'dob' => '1979-02-04 08:30:00',
            'weight' => 94.50,
            'likes_php' => TRUE,
            'home' => new \GDS\Property\Geopoint(1.23, 4.56)
        ]);
        $this->apiProxyMock->verify();
    }

    /**
     * @todo Fetch with Ancestors+2
     */

    /**
     * Fetch with string list
     */
    public function testFetchWithStringListResult()
    {

        $obj_response = new \google\appengine\datastore\v4\LookupResponse();
        $obj_found = $obj_response->addFound();

        $obj_entity = $obj_found->mutableEntity();
        $obj_result_key = $obj_entity->mutableKey();
        $obj_result_kpe = $obj_result_key->addPathElement();
        $obj_result_kpe->setKind('Book');
        $obj_result_kpe->setId(123456789);

        $obj_result_property = $obj_entity->addProperty();
        $obj_result_property->setName('director');
        $obj_val = $obj_result_property->mutableValue();
        $obj_val->setStringValue('Robert Zemeckis');

        $obj_result_property2 = $obj_entity->addProperty();
        $obj_result_property2->setName('dedications');
        $obj_val2 = $obj_result_property2->mutableValue();
        $obj_val2->addListValue()->setStringValue('Marty McFly');
        $obj_val2->addListValue()->setStringValue('Emmett Brown');

        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $this->getBasicBookByIdRequest(), $obj_response);

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_result = $obj_store->fetchById(123456789);

        $this->assertInstanceOf('\\GDS\\Entity', $obj_result);
        $this->assertEquals(2, count($obj_result->getData()));
        $this->assertEquals('Book', $obj_result->getKind());
        $this->assertEquals(123456789, $obj_result->getKeyId());
        $this->assertEquals('Robert Zemeckis', $obj_result->director);
        $this->assertEquals(['Marty McFly', 'Emmett Brown'] ,$obj_result->dedications);

        $this->apiProxyMock->verify();
    }

}
