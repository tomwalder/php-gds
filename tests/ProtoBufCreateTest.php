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
use google\appengine\testing\ApiProxyTestBase;

/**
 * Tests for Protocol Buffer Creates
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class ProtoBufCreateTest extends ApiProxyTestBase {

    /**
     * Insert One
     */
    public function testUpsertOneAutoId()
    {
        $obj_request = new \google\appengine\datastore\v4\CommitRequest();
        $obj_request->setMode(\google\appengine\datastore\v4\CommitRequest\Mode::NON_TRANSACTIONAL);
        $obj_mutation = $obj_request->mutableDeprecatedMutation();

        $obj_entity = $obj_mutation->addInsertAutoId();
        $obj_key = $obj_entity->mutableKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');

        $obj_property = $obj_entity->addProperty();
        $obj_property->setName('nickname');
        $obj_val = $obj_property->mutableValue();
        $obj_val->setIndexed(TRUE);
        $obj_val->setStringValue('Romeo');

        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $obj_request, new \google\appengine\datastore\v4\CommitResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store($obj_gateway, 'Book');
        $obj_ex = NULL;
        try {
            $obj_store->upsert($obj_store->createEntity([
                'nickname' => 'Romeo'
            ]));
        } catch (\Exception $obj_ex) {}

        $this->assertEquals($obj_ex, new \Exception('Mismatch count of requested & returned Auto IDs'));
        $this->apiProxyMock->verify();
    }

    /**
     * Insert one with a Key Name
     */
    public function testUpsertOneWithKeyName()
    {
        $obj_request = new \google\appengine\datastore\v4\CommitRequest();
        $obj_request->setMode(\google\appengine\datastore\v4\CommitRequest\Mode::NON_TRANSACTIONAL);
        $obj_mutation = $obj_request->mutableDeprecatedMutation();

        $obj_entity = $obj_mutation->addUpsert();
        $obj_key = $obj_entity->mutableKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setName('RomeoAndJuliet');

        $obj_property = $obj_entity->addProperty();
        $obj_property->setName('nickname');
        $obj_val = $obj_property->mutableValue();
        $obj_val->setIndexed(TRUE);
        $obj_val->setStringValue('Romeo');

        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $obj_request, new \google\appengine\datastore\v4\CommitResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store($obj_gateway, 'Book');
        $obj_ex = NULL;
        $obj_store->upsert(
            $obj_store->createEntity([
                'nickname' => 'Romeo'
            ])->setKeyName('RomeoAndJuliet')
        );

        $this->apiProxyMock->verify();
    }
}
