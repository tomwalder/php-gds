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
     * @todo Fetch with an Integer parameter
     */

    /**
     * @todo Fetch with a DateTime parameter
     */

    /**
     * @todo Fetch with a root GDS\Entity parameter
     */

    /**
     * @todo Fetch with an GDS\Entity parameter (with ancestors)
     */

    /**
     * @todo Fetch with a multiple mixed type parameters
     */



}
