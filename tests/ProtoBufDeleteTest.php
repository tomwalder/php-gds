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
 * Tests for Protocol Buffer Deletes
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class ProtoBufDeleteTest extends GDSTest {

    /**
     * Delete one
     */
    public function testDeleteOne()
    {
        $obj_request = new \google\appengine\datastore\v4\CommitRequest();
        $obj_request->setMode(\google\appengine\datastore\v4\CommitRequest\Mode::NON_TRANSACTIONAL);
        $obj_mutation = $obj_request->mutableDeprecatedMutation();

        $obj_key = $obj_mutation->addDelete();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setId(9876543321);

        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $obj_request, new \google\appengine\datastore\v4\CommitResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->delete($obj_store->createEntity()->setKeyId(9876543321));

        $this->assertEquals($obj_result, TRUE);
        $this->apiProxyMock->verify();
    }

    /**
     * Delete many
     */
    public function testDeleteMany()
    {
        $obj_request = new \google\appengine\datastore\v4\CommitRequest();
        $obj_request->setMode(\google\appengine\datastore\v4\CommitRequest\Mode::NON_TRANSACTIONAL);
        $obj_mutation = $obj_request->mutableDeprecatedMutation();

        $obj_key = $obj_mutation->addDelete();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setId(9876543321);

        $obj_key = $obj_mutation->addDelete();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setId(9876543322);

        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $obj_request, new \google\appengine\datastore\v4\CommitResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->delete([
            $obj_store->createEntity()->setKeyId(9876543321),
            $obj_store->createEntity()->setKeyId(9876543322),
        ]);

        $this->assertEquals($obj_result, TRUE);
        $this->apiProxyMock->verify();
    }
}
