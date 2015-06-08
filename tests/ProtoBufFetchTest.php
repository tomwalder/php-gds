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
        $this->assertEquals($obj_result, NULL);

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
     * Fetch by Id
     */
    public function testFetchById()
    {
        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $this->getBasicBookByIdRequest(), new \google\appengine\datastore\v4\LookupResponse());
        $obj_result = $this->createBasicStore()->fetchById(123456789);
        $this->assertEquals($obj_result, NULL);
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

}
