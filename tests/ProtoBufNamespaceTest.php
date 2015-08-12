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
     * Build a name-spaced Store
     *
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
     * Test upsert, with namespace
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

    /**
     * Fetch by Name, with namespace
     */
    public function testFetchByName()
    {
        $obj_request = new \google\appengine\datastore\v4\LookupRequest();
        $obj_request->mutableReadOptions();
        $obj_key = $obj_request->addKey();
        $obj_key->mutablePartitionId()->setDatasetId('Dataset')->setNamespace('Test');
        $obj_key->addPathElement()->setKind('Book')->setName('PoEAA');

        $this->apiProxyMock->expectCall('datastore_v4', 'Lookup', $obj_request, new \google\appengine\datastore\v4\LookupResponse());

        $obj_result = $this->getBookstoreWithTestNamespace()->fetchByName('PoEAA');
        $this->assertEquals($obj_result, null);

        $this->apiProxyMock->verify();
    }

    /**
     * Delete one, with namespace
     */
    public function testDeleteOne()
    {
        $obj_request = new \google\appengine\datastore\v4\CommitRequest();
        $obj_request->setMode(\google\appengine\datastore\v4\CommitRequest\Mode::NON_TRANSACTIONAL);
        $obj_mutation = $obj_request->mutableDeprecatedMutation();
        $obj_key = $obj_mutation->addDelete();
        $obj_key->mutablePartitionId()->setDatasetId('Dataset')->setNamespace('Test');
        $obj_key->addPathElement()->setKind('Book')->setName('PoEAA');

        $this->apiProxyMock->expectCall('datastore_v4', 'Commit', $obj_request, new \google\appengine\datastore\v4\CommitResponse());

        $obj_store = $this->getBookstoreWithTestNamespace();
        $obj_result = $obj_store->delete($obj_store->createEntity()->setKeyName('PoEAA'));

        $this->assertEquals($obj_result, TRUE);
        $this->apiProxyMock->verify();
    }

    /**
     * GQL Fetch ONE with one string parameter and namespace
     */
    public function testFetchOneStringParam()
    {
        $str_gql = "SELECT * FROM Kind WHERE property = @param";
        
        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_request->mutablePartitionId()->setDatasetId('Dataset')->setNamespace('Test');

        $obj_gql_query = $obj_request->mutableGqlQuery()->setAllowLiteral(TRUE)->setQueryString($str_gql . " LIMIT 1");
        $obj_arg = $obj_gql_query->addNameArg();
        $obj_arg->setName('param');
        $obj_arg->mutableValue()->setStringValue('test');

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_result = $this->getBookstoreWithTestNamespace()->fetchOne($str_gql, ['param' => 'test']);

        $this->assertEquals($obj_result, null);
        $this->apiProxyMock->verify();
    }

}
