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
    private $obj_fake_io = null;

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
     * Test FetchById Request
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
     * Test FetchByName Request
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
     * Test upsert Request
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
     * Test delete Request
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
     * Test GQL Request
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
     * Test FetchById with Namespace Request
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
     * Test transaction Request
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
     * Test GQL Request
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

    /**
     * Put with all supported data types (dynamic Schema)
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
     */
    public function testUpsertVariantDataTypes()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Person', $obj_gateway);

        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/commit',
            '{"mode":"NON_TRANSACTIONAL","mutation":{"insertAutoId":[{"key":{"path":[{"kind":"Person"}]},"properties":{"name":{"indexed":true,"stringValue":"Tom"},"age":{"indexed":true,"integerValue":36},"dob":{"dateTimeValue":"1979-02-04T08:30:00+00:00","indexed":true},"weight":{"doubleValue":94.5,"indexed":true},"likes_php":{"booleanValue":true,"indexed":true},"friends":{"listValue":[{"indexed":true,"stringValue":"Tom"},{"indexed":true,"stringValue":"Dick"},{"indexed":true,"stringValue":"Harry"}]}}}]}}'
        );

        $obj_gds_entity = new GDS\Entity();
        $obj_gds_entity->name = 'Tom';
        $obj_gds_entity->age = 36;
        $obj_gds_entity->dob = new DateTime('1979-02-04 08:30:00');
        $obj_gds_entity->weight = 94.50;
        $obj_gds_entity->likes_php = TRUE;
        $obj_gds_entity->friends = ['Tom', 'Dick', 'Harry'];
        $obj_store->upsert($obj_gds_entity);
    }


    /**
     * Test GQL Request with variant data types
     */
    public function testGqlFetchVariantDataResult()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Person', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/runQuery',
            '{"gqlQuery":{"allowLiteral":true,"queryString":"SELECT * FROM Person LIMIT @intPageSize ","nameArgs":[{"name":"intPageSize","value":{"integerValue":1}}]}}',
            '{"batch": {"entityResultType": "FULL","entityResults": [{"entity": {"key":{"partitionId": {"datasetId": "Dataset"},"path":[{"kind":"Person","id": "4804129360707584"}]},"properties":{"name":{"indexed":true,"stringValue":"Tom"},"age":{"indexed":true,"integerValue":36},"dob":{"dateTimeValue":"2015-06-23T09:20:06.000Z","indexed":true},"weight":{"doubleValue":94.5,"indexed":true},"likes_php":{"booleanValue":true,"indexed":true},"friends":{"listValue":[{"indexed":true,"stringValue":"Tom"},{"indexed":true,"stringValue":"Dick"},{"indexed":true,"stringValue":"Harry"}]}}}}],"endCursor": "CiQSHmoJc35waHAtZ2RzchELEgRCb29rGICAgMDIqsQIDBgAIAA=","moreResults": "MORE_RESULTS_AFTER_LIMIT","skippedResults": null}}'
        );
        $arr_result = $obj_store->query("SELECT * FROM Person")->fetchPage(1);

        $this->assertTrue(is_array($arr_result));
        $this->assertEquals(1, count($arr_result));
        $obj_result = $arr_result[0];
        $this->assertInstanceOf('\\GDS\\Entity', $obj_result);
        $this->assertEquals('4804129360707584', $obj_result->getKeyId());
        $this->assertEquals([
            'name' => 'Tom',
            'age' => 36,
            'dob' => '2015-06-23T09:20:06.000Z',
            'likes_php' => true,
            'friends' => ['Tom', 'Dick', 'Harry'],
            'weight' => 94.5
        ], $obj_result->getData());
        $this->assertEquals('CiQSHmoJc35waHAtZ2RzchELEgRCb29rGICAgMDIqsQIDBgAIAA=', $obj_store->getCursor());

    }

    /**
     * Test Upsert With Schema
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
     */
    public function testUpsertWithSchema()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_schema = (new \GDS\Schema('Person'))
            ->addString('name')
            ->addInteger('age', FALSE)
            ->addDatetime('dob')
            ->addDatetime('last_seen')
            ->addFloat('weight')
            ->addBoolean('likes_php')
            ->addStringList('friends');
        $obj_store = new \GDS\Store($obj_schema, $obj_gateway);

        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/commit',
            '{"mode":"NON_TRANSACTIONAL","mutation":{"insertAutoId":[{"key":{"path":[{"kind":"Person"}]},"properties":{"name":{"indexed":true,"stringValue":"Tom"},"age":{"indexed":false,"integerValue":36},"dob":{"dateTimeValue":"1979-02-04T08:30:00+00:00","indexed":true},"last_seen":{"dateTimeValue":"2015-02-04T08:30:00+00:00","indexed":true},"weight":{"doubleValue":94.5,"indexed":true},"likes_php":{"booleanValue":true,"indexed":true},"friends":{"listValue":[{"indexed":true,"stringValue":"Tom"},{"indexed":true,"stringValue":"Dick"},{"indexed":true,"stringValue":"Harry"}]}}}]}}'
        );

        $obj_gds_entity = new GDS\Entity();
        $obj_gds_entity->name = 'Tom';
        $obj_gds_entity->age = 36;
        $obj_gds_entity->dob = new DateTime('1979-02-04 08:30:00');
        $obj_gds_entity->last_seen = '2015-02-04 08:30:00';
        $obj_gds_entity->weight = 94.50;
        $obj_gds_entity->likes_php = TRUE;
        $obj_gds_entity->friends = ['Tom', 'Dick', 'Harry'];
        $obj_store->upsert($obj_gds_entity);
    }


    /**
     * Test Fetch With Schema
     */
    public function testFetchWithSchema()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_schema = (new \GDS\Schema('Person'))
            ->addString('name')
            ->addInteger('age', FALSE)
            ->addDatetime('dob')
            ->addDatetime('last_seen')
            ->addFloat('weight')
            ->addBoolean('likes_php')
            ->addStringList('friends');
        $obj_store = new \GDS\Store($obj_schema, $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/runQuery',
            '{"gqlQuery":{"allowLiteral":true,"queryString":"SELECT * FROM Person LIMIT @intPageSize ","nameArgs":[{"name":"intPageSize","value":{"integerValue":1}}]}}',
            '{"batch": {"entityResultType": "FULL","entityResults": [{"entity": {"key":{"partitionId": {"datasetId": "Dataset"},"path":[{"kind":"Person","id": "4804129360707584"}]},"properties":{"name":{"indexed":true,"stringValue":"Tom"},"age":{"indexed":true,"integerValue":36},"dob":{"dateTimeValue":"2015-06-23T09:20:06.000Z","indexed":true},"weight":{"doubleValue":94.5,"indexed":true},"likes_php":{"booleanValue":true,"indexed":true},"friends":{"listValue":[{"indexed":true,"stringValue":"Tom"},{"indexed":true,"stringValue":"Dick"},{"indexed":true,"stringValue":"Harry"}]}}}}],"endCursor": "CiQSHmoJc35waHAtZ2RzchELEgRCb29rGICAgMDIqsQIDBgAIAA=","moreResults": "MORE_RESULTS_AFTER_LIMIT","skippedResults": null}}'
        );
        $arr_result = $obj_store->query("SELECT * FROM Person")->fetchPage(1);

        $this->assertTrue(is_array($arr_result));
        $this->assertEquals(1, count($arr_result));
        $obj_result = $arr_result[0];
        $this->assertInstanceOf('\\GDS\\Entity', $obj_result);
        $this->assertEquals('4804129360707584', $obj_result->getKeyId());
        $this->assertEquals([
            'name' => 'Tom',
            'age' => 36,
            'dob' => '2015-06-23T09:20:06.000Z',
            'likes_php' => true,
            'friends' => ['Tom', 'Dick', 'Harry'],
            'weight' => 94.5
        ], $obj_result->getData());
        $this->assertEquals('CiQSHmoJc35waHAtZ2RzchELEgRCb29rGICAgMDIqsQIDBgAIAA=', $obj_store->getCursor());
    }

    /**
     * Test Upsert With Ancestors
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
     */
    public function testUpsertWithArrayAncestors()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);

        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/commit',
            '{"mode":"NON_TRANSACTIONAL","mutation":{"insertAutoId":[{"key":{"path":[{"kind":"Author","name":"WilliamShakespeare"},{"kind":"Book"}]},"properties":{"nickname":{"indexed":true,"stringValue":"Romeo"}}}]}}'
        );

        $obj_book = $obj_store->createEntity([
            'nickname' => 'Romeo'
        ]);
        $obj_book->setAncestry([[
            'kind' => 'Author',
            'name' => 'WilliamShakespeare'
        ]]);
        $obj_store->upsert($obj_book);
    }

    /**
     * Test Fetch With Ancestors
     */
    public function testFetchWithAncestors()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Person', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/runQuery',
            '{"gqlQuery":{"allowLiteral":true,"queryString":"SELECT * FROM Person LIMIT @intPageSize ","nameArgs":[{"name":"intPageSize","value":{"integerValue":1}}]}}',
            '{"batch": {"entityResultType": "FULL","entityResults": [{"entity": {"key":{"partitionId": {"datasetId": "Dataset"},"path":[{"kind":"Person","name":"Johnny"},{"kind":"Person","id": "4804129360707584"}]},"properties":{"name":{"indexed":true,"stringValue":"Tom"},"age":{"indexed":true,"integerValue":36},"dob":{"dateTimeValue":"1979-02-04T08:30:00+00:00","indexed":true},"weight":{"doubleValue":94.5,"indexed":true},"likes_php":{"booleanValue":true,"indexed":true},"friends":{"listValue":[{"indexed":true,"stringValue":"Tom"},{"indexed":true,"stringValue":"Dick"},{"indexed":true,"stringValue":"Harry"}]}}}}],"endCursor": "CiQSHmoJc35waHAtZ2RzchELEgRCb29rGICAgMDIqsQIDBgAIAA=","moreResults": "MORE_RESULTS_AFTER_LIMIT","skippedResults": null}}'
        );
        $arr_result = $obj_store->query("SELECT * FROM Person")->fetchPage(1);

        $this->assertTrue(is_array($arr_result));
        $this->assertEquals(1, count($arr_result));
        $obj_result = $arr_result[0];
        $this->assertInstanceOf('\\GDS\\Entity', $obj_result);
        $this->assertEquals('4804129360707584', $obj_result->getKeyId());
        $this->assertEquals([[
            'kind' => 'Person',
            'id' => null,
            'name' => 'Johnny'
        ]], $obj_result->getAncestry());
        $this->assertEquals('CiQSHmoJc35waHAtZ2RzchELEgRCb29rGICAgMDIqsQIDBgAIAA=', $obj_store->getCursor());
    }

    /**
     * Test upsert Request with key name
     */
    public function testUpsertWithKeyNameRequest()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Film', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/commit',
            '{"mode":"NON_TRANSACTIONAL","mutation":{"upsert":[{"key":{"path":[{"kind":"Film","name":"B2TF"}]},"properties":{"title":{"indexed":true,"stringValue":"Back to the Future"}}}]}}'
        );
        $obj_film = $obj_store->createEntity([
            'title' => 'Back to the Future'
        ]);
        $obj_film->setKeyName('B2TF');
        $obj_store->upsert($obj_film);
    }

    /**
     * Test upsert in transaction
     */
    public function testUpsertWithKeyNameInTransactionRequest()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Film', $obj_gateway);

        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/beginTransaction',
            '{}',
            '{"transaction":"EeDoHGJsLR4eGjkABRmGMYV-Vj6Gtwn3ayLOvPX8ccUzuR4NZG0MMhmD28O-3gTTwdIUINZeJBk22kubBQPd0-Nz1sY="}'
        );
        $obj_store->beginTransaction();


        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/commit',
            '{"mode":"TRANSACTIONAL","transaction":"EeDoHGJsLR4eGjkABRmGMYV-Vj6Gtwn3ayLOvPX8ccUzuR4NZG0MMhmD28O-3gTTwdIUINZeJBk22kubBQPd0-Nz1sY=","mutation":{"upsert":[{"key":{"path":[{"kind":"Film","name":"B2TF"}]},"properties":{"title":{"indexed":true,"stringValue":"Back to the Future"}}}]}}'
        );
        $obj_film = $obj_store->createEntity([
            'title' => 'Back to the Future'
        ]);
        $obj_film->setKeyName('B2TF');
        $obj_store->upsert($obj_film);
    }

    /**
     * Test Failure on cross-group transactions
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Cross group transactions not supported over JSON API

     */
    public function testFailCrossGroup()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Film', $obj_gateway);
        $obj_store->beginTransaction(TRUE);
    }

}