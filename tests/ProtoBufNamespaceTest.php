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
 * Tests for Protocol Buffer Namespace usage
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class ProtoBufNamepsaceTest extends GDSTest {

    /**
     * @return \GDS\Store
     */
    private function getBookstoreWithTestNamespace()
    {
        return new GDS\Store(
            (new GDS\Schema('Book'))->addString('title'),
            new GDS\Gateway\ProtoBuf('Dataset', 'Test')
        );
    }

    /**
     * Test basic upsert
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
     */
    public function testUpsert()
    {
        $obj_request = new \google\appengine\datastore\v4\CommitRequest();
        $obj_request->setMode(\google\appengine\datastore\v4\CommitRequest\Mode::NON_TRANSACTIONAL);

        $obj_entity = $obj_request->mutableDeprecatedMutation()->addInsertAutoId();
        $obj_key = $obj_entity->mutableKey();
        $obj_key->mutablePartitionId()->setDatasetId('Dataset')->setNamespace('Test');
        $obj_key->addPathElement()->setKind('Book');

        $obj_entity->addProperty()->setName('title')->mutableValue()->setIndexed(TRUE)->setStringValue('Patterns of Enterprise Application Architecture');

        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $obj_request, new \google\appengine\datastore\v4\CommitResponse());

        $obj_store = $this->getBookstoreWithTestNamespace();
        $obj_store->upsert($obj_store->createEntity([
            'title' => 'Patterns of Enterprise Application Architecture'
        ]));

        $this->apiProxyMock->verify();
    }

}
