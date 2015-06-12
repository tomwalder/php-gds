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
 * Tests for Protocol Buffer GQL queries
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class ProtoBufGqlTest extends GDSTest {

    /**
     * Fetch one test
     */
    public function testFetchOneNoParams()
    {
        $str_gql = "SELECT * FROM Kind";
        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql . " LIMIT 1");

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->fetchOne($str_gql);

        $this->assertEquals($obj_result, NULL);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch page test
     */
    public function testFetchPageNoParams(){
        $str_gql = "SELECT * FROM Kind";
        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql . " LIMIT 11 ");

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->query($str_gql)->fetchPage(11);

        $this->assertEquals($obj_result, []);
        $this->apiProxyMock->verify();
    }

    /**
     * GQL Fetch ONE with one string parameter
     */
    public function testFetchOneStringParam()
    {
        $str_gql = "SELECT * FROM Kind WHERE property = @param";
        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');

        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql . " LIMIT 1");
        $obj_arg = $obj_gql_query->addNameArg();
        $obj_arg->setName('param');
        $obj_arg->mutableValue()->setStringValue('test');

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->fetchOne($str_gql, ['param' => 'test']);

        $this->assertEquals($obj_result, NULL);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch all
     */
    public function testFetchAllNoParams()
    {
        $str_gql = "SELECT * FROM Kind";
        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql);

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->fetchAll($str_gql);

        $this->assertEquals($obj_result, []);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch with a multiple mixed type parameters
     */
    public function testFetchOneMultiParam()
    {
        $str_gql = "SELECT * FROM Kind WHERE property1 = @param1 AND property2 = @param2 AND property3 = @param3";
        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');

        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql . " LIMIT 1");

        $obj_arg1 = $obj_gql_query->addNameArg();
        $obj_arg1->setName('param1');
        $obj_arg1->mutableValue()->setIntegerValue(123);

        $obj_arg2 = $obj_gql_query->addNameArg();
        $obj_arg2->setName('param2');
        $obj_arg2->mutableValue()->setTimestampMicrosecondsValue(286965000000000);

        $obj_arg3 = $obj_gql_query->addNameArg();
        $obj_arg3->setName('param3');
        $obj_arg3->mutableValue()->setStringValue('test3');

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->fetchOne($str_gql, [
            'param1' => 123,
            'param2' => new DateTime('1979-02-04 08:30:00'),
            'param3' => 'test3'
        ]);

        $this->assertEquals($obj_result, NULL);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch with a root GDS\Entity parameter
     */
    public function testFetchOneEntityParam()
    {
        $str_gql = "SELECT * FROM Kind WHERE property = @param";
        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');

        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql . " LIMIT 1");

        $obj_arg = $obj_gql_query->addNameArg();
        $obj_arg->setName('param');
        $obj_key = $obj_arg->mutableValue()->mutableKeyValue();
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Book');
        $obj_kpe->setName('test-key-name-here');

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_entity = (new GDS\Entity())->setKind('Book')->setKeyName('test-key-name-here');

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->fetchOne($str_gql, ['param' => $obj_entity]);

        $this->assertEquals($obj_result, NULL);
        $this->apiProxyMock->verify();
    }

    /**
     * @todo Fetch with an GDS\Entity parameter (with ancestors)
     */

}