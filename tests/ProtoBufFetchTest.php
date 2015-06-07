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
     * Fetch by Id
     */
    public function testFetchById()
    {
        $obj_request = new \google\appengine\datastore\v4\LookupRequest();
        $obj_request->mutableReadOptions();
        $obj_key = $obj_request->addKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setId(123456789);

        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $obj_request, new \google\appengine\datastore\v4\LookupResponse());

        $obj_result = $this->createBasicStore()->fetchById(123456789);
        $this->assertEquals($obj_result, NULL);

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
