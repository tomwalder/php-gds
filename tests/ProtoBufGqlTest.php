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
     * This request is re-used a lot in the tests
     *
     * @return \google\appengine\datastore\v4\RunQueryRequest
     */
    private function getBasicFetchRequest()
    {
        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        return $obj_request;
    }

    /**
     * Fetch one test
     */
    public function testFetchOneNoParams()
    {
        $str_gql = "SELECT * FROM Kind";
        $obj_request = $this->getBasicFetchRequest();
        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql . " LIMIT 1");

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->fetchOne($str_gql);

        $this->assertEquals($obj_result, null);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch page test
     */
    public function testFetchPageNoParams(){
        $str_gql = "SELECT * FROM Kind";
        $obj_request = $this->getBasicFetchRequest();
        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql . " LIMIT @intPageSize ");

        $obj_arg = $obj_gql_query->addNameArg();
        $obj_arg->setName('intPageSize');
        $obj_arg->mutableValue()->setIntegerValue(11);

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->query($str_gql)->fetchPage(11);

        $this->assertEquals($obj_result, []);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch Page with record integer offset
     */
    public function testFetchPageIntegerOffset(){
        $str_gql = "SELECT * FROM Kind";
        $obj_request = $this->getBasicFetchRequest();
        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql . " LIMIT @intPageSize OFFSET @intOffset");

        $obj_arg = $obj_gql_query->addNameArg();
        $obj_arg->setName('intPageSize');
        $obj_arg->mutableValue()->setIntegerValue(11);

        $obj_arg_offset = $obj_gql_query->addNameArg();
        $obj_arg_offset->setName('intOffset');
        $obj_arg_offset->mutableValue()->setIntegerValue(22);

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->query($str_gql)->fetchPage(11, 22);

        $this->assertEquals($obj_result, []);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch Page with cursor offset
     */
    public function testFetchPageCursorOffset(){
        $str_gql = "SELECT * FROM Kind";
        $obj_request = $this->getBasicFetchRequest();

        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql . " LIMIT @intPageSize OFFSET @startCursor");

        $obj_arg = $obj_gql_query->addNameArg();
        $obj_arg->setName('intPageSize');
        $obj_arg->mutableValue()->setIntegerValue(11);

        $obj_arg_offset = $obj_gql_query->addNameArg();
        $obj_arg_offset->setName('startCursor');
        $obj_arg_offset->setCursor('some-cursor-string');

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->query($str_gql)->fetchPage(11, 'some-cursor-string');

        $this->assertEquals($obj_result, []);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch Page with cursor offset from historical value, using 'default query'
     */
    public function testFetchPageHistoricalCursor() {

        $obj_request = $this->getBasicFetchRequest();

        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString("SELECT * FROM `Person` ORDER BY __key__ ASC LIMIT @intPageSize OFFSET @startCursor");

        $obj_arg = $obj_gql_query->addNameArg();
        $obj_arg->setName('intPageSize');
        $obj_arg->mutableValue()->setIntegerValue(11);

        $obj_arg_offset = $obj_gql_query->addNameArg();
        $obj_arg_offset->setName('startCursor');
        $obj_arg_offset->setCursor('some-historical-cursor');

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_schema = (new \GDS\Schema('Person'))->addString('name')->addInteger('age');
        $obj_store = new GDS\Store($obj_schema, $obj_gateway);
        $obj_store->setCursor('some-historical-cursor');

        $arr_result = $obj_store->fetchPage(11);
        $this->assertEquals($arr_result, []);
        $this->apiProxyMock->verify();
    }

    /**
     * GQL Fetch ONE with one string parameter
     */
    public function testFetchOneStringParam()
    {
        $str_gql = "SELECT * FROM Kind WHERE property = @param";
        $obj_request = $this->getBasicFetchRequest();

        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql . " LIMIT 1");
        $obj_arg = $obj_gql_query->addNameArg();
        $obj_arg->setName('param');
        $obj_arg->mutableValue()->setStringValue('test');

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_store = $this->createBasicStore();
        $obj_result = $obj_store->fetchOne($str_gql, ['param' => 'test']);

        $this->assertEquals($obj_result, null);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch all
     */
    public function testFetchAllNoParams()
    {
        $str_gql = "SELECT * FROM Kind";
        $obj_request = $this->getBasicFetchRequest();
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
        $obj_request = $this->getBasicFetchRequest();

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

        $this->assertEquals($obj_result, null);
        $this->apiProxyMock->verify();
    }

    /**
     * Fetch Entity Group
     */
    public function testFetchEntityGroup()
    {
        $str_gql = "SELECT * FROM `Book` WHERE __key__ HAS ANCESTOR @ancestorKey";
        $obj_request = $this->getBasicFetchRequest();

        $obj_gql_query = $obj_request->mutableGqlQuery();
        $obj_gql_query->setAllowLiteral(TRUE);
        $obj_gql_query->setQueryString($str_gql);

        $obj_arg = $obj_gql_query->addNameArg();
        $obj_arg->setName('ancestorKey');
        $obj_key = $obj_arg->mutableValue()->mutableKeyValue();
        $obj_kpe = $obj_key->addPathElement();
        $obj_kpe->setKind('Author');
        $obj_kpe->setName('test-key-name-here');

        $obj_key->mutablePartitionId()->setDatasetId('Dataset');

        $this->apiProxyMock->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_entity = (new GDS\Entity())->setKind('Author')->setKeyName('test-key-name-here');

        $obj_store = $this->createBasicStore();
        $arr_results = $obj_store->fetchEntityGroup($obj_entity);
        $this->assertEquals($arr_results, []);
        $this->apiProxyMock->verify();
    }

    /**
     * Test that we fail on no Schema/Kind
     */
    public function testNoSchema()
    {
        $obj_ex = null;
        try {
            new \GDS\Store();
        } catch (\Exception $obj_ex) {}
        $this->assertEquals($obj_ex, new \Exception('You must provide a Schema or Kind. Alternatively, you can extend GDS\Store and implement the buildSchema() method.'));
    }

    /**
     * @todo Fetch with an GDS\Entity parameter (with ancestors)
     */

}
