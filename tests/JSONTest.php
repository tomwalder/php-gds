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
     */
    private function expectRequest($str_url, $str_req)
    {
        $this->obj_fake_io->expectRequest($str_url, $str_req);
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
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Mismatch count of requested & returned Auto IDs
     */
    public function testUpsertAutoIdRequest()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Film', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/commit',
            '{"mode":"NON_TRANSACTIONAL","mutation":{"insertAutoId":[{"key":{"path":[{"kind":"Film"}]},"properties":{"title":{"indexed":true,"stringValue":"Back to the Future"}}}]}}'
        );
        $obj_store->upsert($obj_store->createEntity([
            'title' => 'Back to the Future'
        ]));
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
     * test begin transaction Request
     */
    public function testBeginTransactionRequest()
    {
        $obj_gateway = new GDS\Gateway\GoogleAPIClient($this->setupTestClient(), 'Dataset');
        $obj_store = new \GDS\Store('Book', $obj_gateway);
        $this->expectRequest(
            'https://www.googleapis.com/datastore/v1beta2/datasets/Dataset/beginTransaction',
            '{}'
        );
        $obj_store->beginTransaction();
    }
}