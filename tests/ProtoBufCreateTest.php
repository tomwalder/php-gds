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
 * Tests for Protocol Buffer Creates
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class ProtoBufCreateTest extends GDSTest {

    /**
     * Insert One
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
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

        $obj_store = $this->createBasicStore();
        $obj_store->upsert($obj_store->createEntity([
            'nickname' => 'Romeo'
        ]));
        $this->apiProxyMock->verify();
    }

    /**
     * Insert One with Schema
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
     */
    public function testUpsertSchemaOneAutoId()
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
        $obj_property->setName('title');
        $obj_val = $obj_property->mutableValue();
        $obj_val->setIndexed(FALSE);
        $obj_val->setStringValue('Romeo and Juliet');
        $obj_property = $obj_entity->addProperty();
        $obj_property->setName('published');
        $obj_val = $obj_property->mutableValue();
        $obj_val->setIndexed(FALSE);
        $obj_val->setTimestampMicrosecondsValue(286965000000000);

        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $obj_request, new \google\appengine\datastore\v4\CommitResponse());

        $obj_schema = (new \GDS\Schema('Book'))
            ->addString('title', FALSE)
            ->addDatetime('published', FALSE);
        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store($obj_schema, $obj_gateway);

        $obj_store->upsert($obj_store->createEntity([
            'title' => 'Romeo and Juliet',
            'published' => '1979-02-04 08:30:00'
        ]));
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

        $obj_store = $this->createBasicStore();
        $obj_ex = null;
        $obj_store->upsert(
            $obj_store->createEntity([
                'nickname' => 'Romeo'
            ])->setKeyName('RomeoAndJuliet')
        );

        $this->apiProxyMock->verify();
    }

    /**
     * Put with all supported data types (dynamic Schema)
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
     */
    public function testUpsertVariantDataTypes()
    {
        $obj_request = new \google\appengine\datastore\v4\CommitRequest();
        $obj_request->setMode(\google\appengine\datastore\v4\CommitRequest\Mode::NON_TRANSACTIONAL);
        $obj_mutation = $obj_request->mutableDeprecatedMutation();

        $obj_entity = $obj_mutation->addInsertAutoId();
        $obj_key = $obj_entity->mutableKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Person');
        $obj_entity->addProperty()->setName('name')->mutableValue()->setIndexed(TRUE)->setStringValue('Tom');
        $obj_entity->addProperty()->setName('age')->mutableValue()->setIndexed(TRUE)->setIntegerValue(36);
        $obj_entity->addProperty()->setName('dob')->mutableValue()->setIndexed(TRUE)->setTimestampMicrosecondsValue(286965000000000);
        $obj_entity->addProperty()->setName('weight')->mutableValue()->setIndexed(TRUE)->setDoubleValue(94.50);
        $obj_entity->addProperty()->setName('likes_php')->mutableValue()->setIndexed(TRUE)->setBooleanValue(TRUE);
        $obj_entity->addProperty()->setName('home')->mutableValue()->setIndexed(TRUE)->mutableGeoPointValue()->setLatitude(1.23)->setLongitude(4.56);

        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $obj_request, new \google\appengine\datastore\v4\CommitResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Person', $obj_gateway);
        $obj_gds_entity = new GDS\Entity();
        $obj_gds_entity->name = 'Tom';
        $obj_gds_entity->age = 36;
        $obj_gds_entity->dob = new DateTime('1979-02-04 08:30:00');
        $obj_gds_entity->weight = 94.50;
        $obj_gds_entity->likes_php = TRUE;
        $obj_gds_entity->home = (new \GDS\Property\Geopoint(1.23, 4.56));
        $obj_store->upsert($obj_gds_entity);

        $this->apiProxyMock->verify();
    }

    /**
     * Prepare a request for Ancestor testing
     *
     * @return \google\appengine\datastore\v4\CommitRequest
     */
    private function getUpsertRequestWithBookAndAuthor()
    {
        $obj_request = new \google\appengine\datastore\v4\CommitRequest();
        $obj_request->setMode(\google\appengine\datastore\v4\CommitRequest\Mode::NON_TRANSACTIONAL);
        $obj_mutation = $obj_request->mutableDeprecatedMutation();

        $obj_entity = $obj_mutation->addInsertAutoId();
        $obj_key = $obj_entity->mutableKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Author');
        $obj_kpe->setName('WilliamShakespeare');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_property = $obj_entity->addProperty();
        $obj_property->setName('nickname');
        $obj_val = $obj_property->mutableValue();
        $obj_val->setIndexed(TRUE);
        $obj_val->setStringValue('Romeo');

        return $obj_request;
    }

    /**
     * Insert One with Parent
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
     */
    public function testUpsertArrayAncestorOneLevel()
    {
        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $this->getUpsertRequestWithBookAndAuthor(), new \google\appengine\datastore\v4\CommitResponse());

        $obj_store = $this->createBasicStore();
        $obj_book = $obj_store->createEntity([
            'nickname' => 'Romeo'
        ]);
        $obj_book->setAncestry([[
            'kind' => 'Author',
            'name' => 'WilliamShakespeare'
        ]]);
        $obj_store->upsert($obj_book);
        $this->apiProxyMock->verify();
    }

    /**
     * Insert One with Parent
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
     */
    public function testUpsertEntityAncestorOneLevel()
    {
        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $this->getUpsertRequestWithBookAndAuthor(), new \google\appengine\datastore\v4\CommitResponse());

        $obj_will = new GDS\Entity();
        $obj_will->setKind('Author');
        $obj_will->setKeyName('WilliamShakespeare');

        $obj_store = $this->createBasicStore();
        $obj_book = $obj_store->createEntity([
            'nickname' => 'Romeo'
        ]);
        $obj_book->setAncestry($obj_will);
        $obj_store->upsert($obj_book);
        $this->apiProxyMock->verify();
    }

    /**
     * Create with 2+ Ancestors
     */
    public function testAncestryFromEntity()
    {

        $obj_schema = (new \GDS\Schema('Child'))->addString('name', true);
        $obj_mapper = new \GDS\Mapper\ProtoBuf();
        $obj_mapper->setSchema($obj_schema);

        $obj_gds_grandparent = new \GDS\Entity();
        $obj_gds_grandparent->setKind('GrandParent');
        $obj_gds_grandparent->setKeyId('123456781');
        $obj_gds_grandparent->name = 'Grandfather';

        $obj_gds_parent = new \GDS\Entity();
        $obj_gds_parent->setKind('Parent');
        $obj_gds_parent->setKeyId('123456782');
        $obj_gds_parent->name = 'Dad';
        $obj_gds_parent->setAncestry($obj_gds_grandparent);

        $obj_gds_child = new \GDS\Entity();
        $obj_gds_child->setKind('Child');
        $obj_gds_child->name = 'Son';
        $obj_gds_child->setAncestry($obj_gds_parent);

        $obj_target_ent = new \google\appengine\datastore\v4\Entity();
        $obj_mapper->mapToGoogle($obj_gds_child, $obj_target_ent);

        /** @var \google\appengine\datastore\v4\Key\PathElement[] $arr_path */
        $arr_path = $obj_target_ent->getKey()->getPathElementList();

        $obj_path_first = $arr_path[0];
        $obj_path_second = $arr_path[1];
        $obj_path_last = $arr_path[2];

        $this->assertEquals('GrandParent', $obj_path_first->getKind());
        $this->assertEquals('123456781', $obj_path_first->getId());

        $this->assertEquals('Parent', $obj_path_second->getKind());
        $this->assertEquals('123456782', $obj_path_second->getId());

        $this->assertEquals('Child', $obj_path_last->getKind());

    }

    /**
     * Create with 2+ Ancestors (from array)
     */
    public function testAncestryFromArray()
    {
        $obj_schema = (new \GDS\Schema('Child'))->addString('name', true);
        $obj_mapper = new \GDS\Mapper\ProtoBuf();
        $obj_mapper->setSchema($obj_schema);

        $obj_gds_child = new \GDS\Entity();
        $obj_gds_child->setKind('Child');
        $obj_gds_child->name = 'Son';
        $obj_gds_child->setAncestry([
            [
                'kind' => 'GrandParent',
                'id' => '123456781'
            ], [
                'kind' => 'Parent',
                'id' => '123456782'
            ]
        ]);

        $obj_target_ent = new \google\appengine\datastore\v4\Entity();
        $obj_mapper->mapToGoogle($obj_gds_child, $obj_target_ent);

        /** @var \google\appengine\datastore\v4\Key\PathElement[] $arr_path */
        $arr_path = $obj_target_ent->getKey()->getPathElementList();

        $obj_path_first = $arr_path[0];
        $obj_path_second = $arr_path[1];
        $obj_path_last = $arr_path[2];

        $this->assertEquals('GrandParent', $obj_path_first->getKind());
        $this->assertEquals('123456781', $obj_path_first->getId());

        $this->assertEquals('Parent', $obj_path_second->getKind());
        $this->assertEquals('123456782', $obj_path_second->getId());

        $this->assertEquals('Child', $obj_path_last->getKind());
    }

    /**
     * Insert with a String List
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
     */
    public function testUpsertStringList()
    {
        $obj_request = new \google\appengine\datastore\v4\CommitRequest();
        $obj_request->setMode(\google\appengine\datastore\v4\CommitRequest\Mode::NON_TRANSACTIONAL);
        $obj_mutation = $obj_request->mutableDeprecatedMutation();

        $obj_entity = $obj_mutation->addInsertAutoId();

        $obj_result_key = $obj_entity->mutableKey();
        $obj_partition = $obj_result_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_result_kpe = $obj_result_key->addPathElement();
        $obj_result_kpe->setKind('Film');

        $obj_result_property = $obj_entity->addProperty();
        $obj_result_property->setName('director');
        $obj_result_property->mutableValue()->setStringValue('Robert Zemeckis')->setIndexed(FALSE);

        $obj_val2 = $obj_entity->addProperty()->setName('dedications')->mutableValue();
        $obj_val2->addListValue()->setStringValue('Marty McFly')->setIndexed(FALSE);
        $obj_val2->addListValue()->setStringValue('Emmett Brown')->setIndexed(FALSE);

        $obj_val3 = $obj_entity->addProperty()->setName('fans')->mutableValue();
        $obj_val3->addListValue()->setStringValue('Tom Walder')->setIndexed(TRUE);

        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $obj_request, new \google\appengine\datastore\v4\CommitResponse());

        $obj_schema = (new \GDS\Schema('Film'))
            ->addString('director', FALSE)
            ->addStringList('dedications', FALSE);
        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store($obj_schema, $obj_gateway);
        $obj_store->upsert($obj_store->createEntity([
            'director' => 'Robert Zemeckis',
            'dedications' => ['Marty McFly', 'Emmett Brown'],
            'fans' => ['Tom Walder']
        ]));
        $this->apiProxyMock->verify();
    }


    /**
     * Insert One WITH result
     */
    public function testUpsertOneAutoIdWithResult()
    {
        $obj_request = new \google\appengine\datastore\v4\CommitRequest();
        $obj_request->setMode(\google\appengine\datastore\v4\CommitRequest\Mode::NON_TRANSACTIONAL);
        $obj_mutation = $obj_request->mutableDeprecatedMutation();
        $obj_entity = $obj_mutation->addInsertAutoId();
        $obj_key = $obj_entity->mutableKey();
        $obj_partition = $obj_key->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Film');
        $obj_property = $obj_entity->addProperty();
        $obj_property->setName('title');
        $obj_val = $obj_property->mutableValue();
        $obj_val->setIndexed(TRUE);
        $obj_val->setStringValue('Back to the Future');

        $obj_response = new \google\appengine\datastore\v4\CommitResponse();
        $obj_mutation_result = $obj_response->mutableDeprecatedMutationResult();
        $obj_ai_key = $obj_mutation_result->addInsertAutoIdKey();
        $obj_ai_kpe = $obj_ai_key->addPathElement();
        $obj_ai_kpe->setKind('Film')->setId(499190400);

        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $obj_request, $obj_response);

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Film', $obj_gateway);
        $obj_book = $obj_store->createEntity([
            'title' => 'Back to the Future'
        ]);
        $obj_store->upsert($obj_book);

        $this->assertEquals(499190400, $obj_book->getKeyId());

        $this->apiProxyMock->verify();
    }

}
