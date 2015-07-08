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
 * Tests for Protocol Buffer Errors
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class ProtoBufErrorTest extends GDSTest {

    /**
     * Missing Dataset
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Could not determine DATASET, please pass to GDS\Gateway\ProtoBuf::__construct()
     */
    public function testMissingDataset()
    {
        new GDS\Gateway\ProtoBuf();
    }

    /**
     * Pass in non-Entity objects
     *
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage You gave me something other than GDS\Entity objects.. not gonna fly!
     */
    public function testDodgyUpsertParams()
    {
        $this->createBasicStore()->upsert([null, FALSE, TRUE, new stdClass()]);
    }

    /**
     * Attempt an upsert with an unrecognised property type
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
     */
    public function testUnknownTypes()
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
        $obj_property->setName('property');
        $obj_val = $obj_property->mutableValue();
        $obj_val->setIndexed(TRUE);
        // $obj_val->setStringValue('');

        $obj_property = $obj_entity->addProperty();
        $obj_property->setName('simple');
        $obj_val = $obj_property->mutableValue();
        $obj_val->setIndexed(TRUE);
        $obj_val->setStringValue('success!');

        $obj_property = $obj_entity->addProperty();
        $obj_property->setName('blank');
        $obj_val = $obj_property->mutableValue();
        $obj_val->setIndexed(TRUE);


        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $obj_request, new \google\appengine\datastore\v4\CommitResponse());

        $obj_store = $this->createBasicStore();
        $obj_store->upsert($obj_store->createEntity([
            'property' => new stdClass(),
            'simple' => new Simple(),
            'blank' => null
        ]));

        $this->apiProxyMock->verify();
    }

}
