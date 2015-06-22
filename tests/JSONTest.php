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
 * Tests
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class JSONTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Google_IO_Fake
     */
    private $obj_fake_io = NULL;

    /**
     * Setup the Google Cient with our 'monitored' HTTP IO
     *
     * @return \Google_Client
     */
    private function setupTestClient()
    {
        $obj_client = new \Google_Client();
        $this->obj_fake_io = new Google_IO_Fake($obj_client);
        $obj_client->setIo($this->obj_fake_io);
        return $obj_client;
    }

    /**
     * Tell the Fake IO what to expect...
     *
     * @param $str_url
     * @param $str_req
     */
    private function expectRequest($str_url, $str_req, $str_response = '{}')
    {
        $this->obj_fake_io->expectRequest($str_url, $str_req, $str_response);
    }

    /**
     * Test creating a \Google_Client
     */
    public function testCreateClient()
    {
        $obj_client = \GDS\Gateway\GoogleAPIClient::createGoogleClient('test-app', 'test@example.com', dirname(__FILE__) . '/base/test.p12');
        $this->assertInstanceOf('\\Google_Client', $obj_client);
        $this->assertInstanceOf('\\Google_Auth_OAuth2', $obj_client->getAuth());
    }

    /**
     * Test creating a \Google_Client from JSON service file
     */
    public function testCreateClientFromJSON()
    {
        $obj_client = \GDS\Gateway\GoogleAPIClient::createClientFromJson(dirname(__FILE__) . '/base/service.json');
        $this->assertInstanceOf('\\Google_Client', $obj_client);
        $this->assertInstanceOf('\\Google_Auth_OAuth2', $obj_client->getAuth());
    }

    /**
     * Test create Gateway
     */
    public function testCreateGateway()
    {
        $obj_client = GDS\Gateway\GoogleAPIClient::createClientFromJson(dirname(__FILE__) . '/base/service.json');
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($obj_client, 'Dataset');
        $this->assertInstanceOf('\\GDS\\Gateway\\GoogleAPIClient', $obj_gateway);
    }

    /**
     * test FetchById Request
     */
    public function testFetchByIdRequest()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/lookup',
            '{"keys":[{"path":[{"id":1234567890,"kind":"Book"}]}]}'
        );
        $obj_store->fetchById(1234567890);
    }

    /**
     * test FetchByName Request
     */
    public function testFetchByNameRequest()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Film', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/lookup',
            '{"keys":[{"path":[{"kind":"Film","name":"B2TF"}]}]}'
        );
        $obj_store->fetchByName('B2TF');
    }

    /**
     * test upsert Request
     */
    public function testUpsertAutoIdRequest()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Film', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/commit',
            '{"mode":"NON_TRANSACTIONAL","mutation":{"insertAutoId":[{"key":{"path":[{"kind":"Film"}]},"properties":{"title":{"indexed":true,"stringValue":"Back to the Future"}}}]}}',
            '{"mutationResult":{"indexUpdates":7,"insertAutoIdKeys":[{"partitionId":{"datasetId": "Dataset"},"path":[{"kind": "Film","id": "987654321"}]}]}}'
        );
        $obj_film = $obj_store->createEntity([
            'title' => 'Back to the Future'
        ]);
        $obj_store->upsert($obj_film);
        $this->assertEquals('987654321', $obj_film->getKeyId());
    }

    /**
     * test delete Request
     */
    public function testDeleteRequest()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Film', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/commit',
            '{"mode":"NON_TRANSACTIONAL","mutation":{"delete":[{"path":[{"kind":"Film","name":"B2TF"}]}]}}'
        );
        $obj_entity = $obj_store->createEntity();
        $obj_entity->setKeyName('B2TF');
        $obj_store->delete($obj_entity);
    }

    /**
     * test GQL Request
     */
    public function testBasicGqlRequest()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Film', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/runQuery',
            '{"gqlQuery":{"allowLiteral":true,"queryString":"SELECT * FROM Film WHERE title = @title LIMIT 1","nameArgs":[{"name":"title","value":{"stringValue":"Back to the Future"}}]}}'
        );
        $obj_store->fetchOne("SELECT * FROM Film WHERE title = @title", ['title' => 'Back to the Future']);
    }

    /**
     * test FetchById with Namespace Request
     */
    public function testNamespacedFetchByIdRequest()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset', 'Spaced');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/lookup',
            '{"keys":[{"path":[{"id":1234567890,"kind":"Book"}],"partitionId":{"namespace":"Spaced"}}]}'
        );
        $obj_store->fetchById(1234567890);
    }

    /**
     * test transaction Request
     */
    public function testBeginAndUseTransactionRequest()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/beginTransaction',
            '{}',
            '{"transaction":"EeDoHGJsLR4eGjkABRmGMYV-Vj6Gtwn3ayLOvPX8ccUzuR4NZG0MMhmD28O-3gTTwdIUINZeJBk22kubBQPd0-Nz1sY="}'
        );
        $obj_store->beginTransaction();

        // And a second test to show we have correctly extracted the Transaction ID
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/runQuery',
            '{"readOptions":{"transaction":"EeDoHGJsLR4eGjkABRmGMYV-Vj6Gtwn3ayLOvPX8ccUzuR4NZG0MMhmD28O-3gTTwdIUINZeJBk22kubBQPd0-Nz1sY="},"gqlQuery":{"allowLiteral":true,"queryString":"SELECT * FROM `Book` ORDER BY __key__ ASC LIMIT 1"}}'
        );
        $obj_store->fetchOne();
    }

    /**
     * test GQL Request
     */
    public function testGqlFetchOneWithResult()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/runQuery',
            '{"gqlQuery":{"allowLiteral":true,"queryString":"SELECT * FROM Book LIMIT @intPageSize ","nameArgs":[{"name":"intPageSize","value":{"integerValue":1}}]}}',
            '{"batch": {"entityResultType": "FULL","entityResults": [{"entity": {"key": {"partitionId": {"datasetId": "Dataset"},"path": [{"kind": "Book","id": "4804129360707584"}]},"properties": {"author": {"stringValue": "William Shakespeare","indexed": false},"title": {"stringValue": "A Midsummer Night\'s Dream","indexed": false},"isbn": {"stringValue": "1853260304"}}}}],"endCursor": "CiQSHmoJc35waHAtZ2RzchELEgRCb29rGICAgMDIqsQIDBgAIAA=","moreResults": "MORE_RESULTS_AFTER_LIMIT","skippedResults": null}}'
        );
        $arr_result = $obj_store->query("SELECT * FROM Book")->fetchPage(1);

        $this->assertTrue(is_array($arr_result));
        $this->assertEquals(1, count($arr_result));
        $obj_result = $arr_result[0];
        $this->assertInstanceOf('\\GDS\\Entity', $obj_result);
        $this->assertEquals('4804129360707584', $obj_result->getKeyId());
        $this->assertEquals('William Shakespeare', $obj_result->author);
        $this->assertEquals('CiQSHmoJc35waHAtZ2RzchELEgRCb29rGICAgMDIqsQIDBgAIAA=', $obj_store->getCursor());

    }
}