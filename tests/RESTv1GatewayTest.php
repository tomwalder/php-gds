<?php
/**
 * Copyright 2016 Tom Walder
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
 * Tests for REST API v1 Gateway
 *
 * @todo Consider storing the request and response payloads as JSON files
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class RESTv1GatewayTest extends \RESTv1Test
{

    /**
     * Test begin transaction
     */
    public function testTransaction()
    {
        $str_txn_ref = 'txn-string-here';
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:beginTransaction', [], ['transaction' => $str_txn_ref]);
        /** @var \GDS\Gateway\RESTv1 $obj_gateway */
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);

        $str_txn = $obj_gateway->beginTransaction();

        $this->assertEquals($str_txn_ref, $str_txn);
        $this->validateHttpClient($obj_http);
    }

    /**
     * Ensure the REST gateway only inits the HTTP client lazily
     */
    public function testHttpClient()
    {
        $obj_gateway = new \GDS\Gateway\RESTv1('test1');
        $this->assertNull($obj_gateway->getHttpClient());

        $obj_gateway = $this->getMockBuilder('\\GDS\\Gateway\\RESTv1')->setMethods(['initHttpClient'])->setConstructorArgs([self::TEST_PROJECT])->getMock();

        $str_txn_ref = 'dfguerfjr';
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:beginTransaction', [], ['transaction' => $str_txn_ref]);

        $obj_gateway->expects($this->once())->method('initHttpClient')->willReturn($obj_http);

        $str_txn = $obj_gateway->beginTransaction();

        $this->assertEquals($str_txn_ref, $str_txn);
        $this->validateHttpClient($obj_http);

    }

    /**
     * Test basic entity delete
     */
    public function testDelete()
    {
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:commit', ['json' => (object)[
            'mode' => 'NON_TRANSACTIONAL',
            'mutations' => [
                (object)[
                    'delete' => (object)[
                        'path' => [
                            (object)[
                                'kind' => 'Test',
                                'id' => '123456789'
                            ]
                        ],
                        'partitionId' => (object)[
                            'projectId' => self::TEST_PROJECT
                        ]
                    ]
                ]
            ]
        ]]);
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);

        $obj_store = new \GDS\Store('Test', $obj_gateway);
        $obj_entity = (new GDS\Entity())->setKeyId('123456789');
        $obj_store->delete([$obj_entity]);

        $this->validateHttpClient($obj_http);
    }

    /**
     * Test basic entity upsert
     */
    public function testBasicUpsert()
    {
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:commit', ['json' => (object)[
            'mode' => 'NON_TRANSACTIONAL',
            'mutations' => [
                (object)[
                    'upsert' => (object)[
                        'key' => (object)[
                            'path' => [
                                (object)[
                                    'kind' => 'Test',
                                    'id' => '123456789'
                                ]
                            ],
                            'partitionId' => (object)[
                                'projectId' => self::TEST_PROJECT
                            ]
                        ],
                        'properties' => (object)[
                            'name' => (object)[
                                'excludeFromIndexes' => false,
                                'stringValue' => 'Tom'
                            ]
                        ]
                    ]
                ]
            ]
        ]]);
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);

        $obj_store = new \GDS\Store('Test', $obj_gateway);
        $obj_entity = new GDS\Entity();
        $obj_entity->setKeyId('123456789');
        $obj_entity->name = 'Tom';
        $obj_store->upsert($obj_entity);

        $this->validateHttpClient($obj_http);
    }

    /**
     * Test transactional entity upsert
     */
    public function testTxnUpsert()
    {

        // First begin the transaction
        $str_txn_ref = 'ghei34g498jhegijv0894hiwgerhiugjreiugh';
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:beginTransaction', [], ['transaction' => $str_txn_ref]);
        /** @var \GDS\Gateway\RESTv1 $obj_gateway */
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);
        $obj_store = new \GDS\Store('Test', $obj_gateway);
        $obj_store->beginTransaction();
        $this->validateHttpClient($obj_http);


        // Now set up the transactional upsert
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:commit', ['json' => (object)[
            'mode' => 'TRANSACTIONAL',
            'transaction' => $str_txn_ref,
            'mutations' => [
                (object)[
                    'upsert' => (object)[
                        'key' => (object)[
                            'path' => [
                                (object)[
                                    'kind' => 'Test',
                                    'id' => '123456789'
                                ]
                            ],
                            'partitionId' => (object)[
                                'projectId' => self::TEST_PROJECT
                            ]
                        ],
                        'properties' => (object)[
                            'name' => (object)[
                                'excludeFromIndexes' => false,
                                'stringValue' => 'Tom'
                            ]
                        ]
                    ]
                ]
            ]
        ]]);
        $obj_gateway->setHttpClient($obj_http);

        // Do the upsert
        $obj_entity = new GDS\Entity();
        $obj_entity->setKeyId('123456789');
        $obj_entity->name = 'Tom';
        $obj_store->upsert($obj_entity);

        // Test the final output
        $this->validateHttpClient($obj_http);
    }

    /**
     * Test transactional entity fetch
     */
    public function testTxnFetch()
    {

        // First begin the transaction
        $str_txn_ref = 'ghei34g498jhegijv0894hiwgerhiugjreiugh';
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:beginTransaction', [], ['transaction' => $str_txn_ref]);
        /** @var \GDS\Gateway\RESTv1 $obj_gateway */
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);
        $obj_store = new \GDS\Store('Test', $obj_gateway);
        $obj_store->beginTransaction();
        $this->validateHttpClient($obj_http);


        // Now set up the transactional fetch
        $str_id = '1263751723';
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:lookup', ['json' => (object)[
            'readOptions' => (object)[
                'transaction' => $str_txn_ref
            ],
            'keys' => [
                (object)[
                    'path' => [
                        (object)[
                            'kind' => 'Test',
                            'id' => $str_id
                        ]
                    ],
                    'partitionId' => (object)[
                        'projectId' => self::TEST_PROJECT
                    ]
                ]
            ]
        ]], [
            'found' => [
                (object)[
                    'entity' => (object)[
                        'key' => (object)[
                            'path' => [
                                (object)[
                                    'kind' => 'Test',
                                    'id' => $str_id
                                ]
                            ]
                        ],
                        'properties' => (object)[
                            'name' => (object)[
                                'excludeFromIndexes' => false,
                                'stringValue' => 'Tom'
                            ]
                        ]
                    ],
                    'version' => '123',
                    'cursor' => 'gfuh37f86gyu23'

                ]
            ]
        ]);

        $obj_gateway->setHttpClient($obj_http);

        $obj_entity = $obj_store->fetchById($str_id);

        $this->assertInstanceOf('\\GDS\\Entity', $obj_entity);
        $this->assertEquals($str_id, $obj_entity->getKeyId());
        $this->assertEquals('Tom', $obj_entity->name);

        $this->validateHttpClient($obj_http);

    }

    /**
     * Test basic entity insert
     */
    public function testBasicInsert()
    {
        $int_new_id = mt_rand(100000, 999999);
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:commit', ['json' => (object)[
            'mode' => 'NON_TRANSACTIONAL',
            'mutations' => [
                (object)[
                    'insert' => (object)[
                        'key' => (object)[
                            'path' => [
                                (object)[
                                    'kind' => 'Test'
                                ]
                            ],
                            'partitionId' => (object)[
                                'projectId' => self::TEST_PROJECT
                            ]
                        ],
                        'properties' => (object)[
                            'name' => (object)[
                                'excludeFromIndexes' => false,
                                'stringValue' => 'Tom'
                            ]
                        ]
                    ]
                ]
            ]
        ]], [
            'mutationResults' => [
                (object)[
                    'key' => (object)[
                        'path' => [
                            (object)[
                                'kind' => 'Test',
                                'id' => $int_new_id
                            ]
                        ],
                        'partitionId' => (object)[
                            'projectId' => self::TEST_PROJECT
                        ]
                    ],
                    'version' => '123'
                ]
            ]
        ]);
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);

        $obj_store = new \GDS\Store('Test', $obj_gateway);
        $obj_entity = new GDS\Entity();
        $obj_entity->name = 'Tom';
        $obj_store->upsert($obj_entity);

        $this->validateHttpClient($obj_http);

        $this->assertEquals($int_new_id, $obj_entity->getKeyId());
    }


    /**
     * Test fetch by single ID
     */
    public function testFetchById()
    {
        $str_id = '1263751723';
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:lookup', ['json' => (object)[
            'keys' => [
                (object)[
                    'path' => [
                        (object)[
                            'kind' => 'Test',
                            'id' => $str_id
                        ]
                    ],
                    'partitionId' => (object)[
                        'projectId' => self::TEST_PROJECT
                    ]
                ]
            ]
        ]], [
            'found' => [
                (object)[
                    'entity' => (object)[
                        'key' => (object)[
                            'path' => [
                                (object)[
                                    'kind' => 'Test',
                                    'id' => $str_id
                                ]
                            ]
                        ],
                        'properties' => (object)[
                            'name' => (object)[
                                'excludeFromIndexes' => false,
                                'stringValue' => 'Tom'
                            ],
                            'age' => (object)[
                                'excludeFromIndexes' => false,
                                'integerValue' => 37
                            ],
                            'dob' => (object)[
                                'excludeFromIndexes' => false,
                                'timestampValue' => "2014-10-02T15:01:23.045123456Z"
                            ],
                            'last_updated' => (object)[
                                'excludeFromIndexes' => false,
                                'timestampValue' => "2012-10-02 15:01:23"
                            ],
                            'likes' => (object)[
                                'arrayValue' => (object)[
                                    'values' => [
                                        (object)[
                                            'excludeFromIndexes' => false,
                                            'stringValue' => 'Beer'
                                        ],
                                        (object)[
                                            'stringValue' => 'Cycling'
                                        ],
                                        (object)[
                                            'stringValue' => 'PHP'
                                        ]
                                    ]
                                ]
                            ],
                            'weight' => (object)[
                                'excludeFromIndexes' => false,
                                'doubleValue' => 85.99
                            ],
                            'author' => (object)[
                                'excludeFromIndexes' => false,
                                'booleanValue' => true
                            ],
                            'chickens' => (object)[
                                'excludeFromIndexes' => false,
                                'nullValue' => null
                            ],
                            'lives' => (object)[
                                'excludeFromIndexes' => false,
                                'geoPointValue' => (object)[
                                    'latitude' => 1.23,
                                    'longitude' => 4.56
                                ]
                            ],

                        ]
                    ],
                    'version' => '123',
                    'cursor' => 'gfuh37f86gyu23'

                ]
            ]
        ]);
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);

        $obj_store = new \GDS\Store('Test', $obj_gateway);
        $obj_entity = $obj_store->fetchById($str_id);

        $this->assertInstanceOf('\\GDS\\Entity', $obj_entity);
        $this->assertEquals($str_id, $obj_entity->getKeyId());
        $this->assertEquals('Tom', $obj_entity->name);
        $this->assertEquals(37, $obj_entity->age);
        $this->assertEquals('2014-10-02 15:01:23', $obj_entity->dob);
        $this->assertEquals('2012-10-02 15:01:23', $obj_entity->last_updated);
        $this->assertTrue(is_array($obj_entity->likes));
        $this->assertEquals(['Beer', 'Cycling', 'PHP'], $obj_entity->likes);
        $this->assertEquals(85.99, $obj_entity->weight);
        $this->assertInstanceOf('\\GDS\\Property\\Geopoint', $obj_entity->lives);
        $this->assertEquals(1.23, $obj_entity->lives->getLatitude());
        $this->assertEquals(4.56, $obj_entity->lives->getLongitude());
        $this->assertTrue($obj_entity->author);
        $this->assertNull($obj_entity->chickens);

        $this->validateHttpClient($obj_http);
    }

    /**
     * Test fetch by single ID using schema
     */
    public function testFetchByIdWithSchema()
    {
        $str_id = '1263751723';
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:lookup', ['json' => (object)[
            'keys' => [
                (object)[
                    'path' => [
                        (object)[
                            'kind' => 'Test',
                            'id' => $str_id
                        ]
                    ],
                    'partitionId' => (object)[
                        'projectId' => self::TEST_PROJECT
                    ]
                ]
            ]
        ]], [
            'found' => [
                (object)[
                    'entity' => (object)[
                        'key' => (object)[
                            'path' => [
                                (object)[
                                    'kind' => 'Test',
                                    'id' => $str_id
                                ]
                            ]
                        ],
                        'properties' => (object)[
                            'name' => (object)[
                                'excludeFromIndexes' => false,
                                'stringValue' => 'Tom'
                            ],
                            'age' => (object)[
                                'excludeFromIndexes' => false,
                                'integerValue' => 37
                            ],
                            'dob' => (object)[
                                'excludeFromIndexes' => false,
                                'timestampValue' => "2014-10-02T15:01:23.045123456Z"
                            ],
                            'likes' => (object)[
                                'arrayValue' => (object)[
                                    'values' => [
                                        (object)[
                                            'excludeFromIndexes' => false,
                                            'stringValue' => 'Beer'
                                        ],
                                        (object)[
                                            'stringValue' => 'Cycling'
                                        ],
                                        (object)[
                                            'stringValue' => 'PHP'
                                        ]
                                    ]
                                ]
                            ],
                            'weight' => (object)[
                                'excludeFromIndexes' => false,
                                'doubleValue' => 85.99
                            ],
                            'author' => (object)[
                                'excludeFromIndexes' => false,
                                'booleanValue' => true
                            ],
                            'chickens' => (object)[
                                'excludeFromIndexes' => false,
                                'nullValue' => null
                            ],
                            'lives' => (object)[
                                'excludeFromIndexes' => false,
                                'geoPointValue' => (object)[
                                    'latitude' => 1.23,
                                    'longitude' => 4.56
                                ]
                            ],

                        ]
                    ],
                    'version' => '123',
                    'cursor' => 'gfuh37f86gyu23'

                ]
            ]
        ]);
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);

        $obj_schema = (new \GDS\Schema('Test'))
            ->addString('name')
            ->addInteger('age')
            ->addDatetime('dob')
            ->addStringList('likes')
            ->addFloat('weight')
            ->addGeopoint('lives')
            ->addBoolean('author')
            ;

        $obj_store = new \GDS\Store($obj_schema, $obj_gateway);
        $obj_entity = $obj_store->fetchById($str_id);

        $this->assertInstanceOf('\\GDS\\Entity', $obj_entity);
        $this->assertEquals($str_id, $obj_entity->getKeyId());
        $this->assertEquals('Tom', $obj_entity->name);
        $this->assertEquals(37, $obj_entity->age);
        $this->assertEquals('2014-10-02 15:01:23', $obj_entity->dob);
        $this->assertTrue(is_array($obj_entity->likes));
        $this->assertEquals(['Beer', 'Cycling', 'PHP'], $obj_entity->likes);
        $this->assertEquals(85.99, $obj_entity->weight);
        $this->assertInstanceOf('\\GDS\\Property\\Geopoint', $obj_entity->lives);
        $this->assertEquals(1.23, $obj_entity->lives->getLatitude());
        $this->assertEquals(4.56, $obj_entity->lives->getLongitude());
        $this->assertTrue($obj_entity->author);
        $this->assertNull($obj_entity->chickens);

        $this->validateHttpClient($obj_http);
    }

    /**
     * Test extraction of 2+ ancestors from GQL Query response
     */
    public function testGqlWithAncestorExtract()
    {
        $str_id = '1263751723';
        $str_id_parent = '1263751724';
        $str_id_grandparent = '1263751725';
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:runQuery', ['json' => (object)[
            'gqlQuery' => (object)[
                'allowLiterals' => true,
                'queryString' => 'SELECT * FROM Test LIMIT 1'
            ],
            'partitionId' => (object)[
                'projectId' => self::TEST_PROJECT
            ]

        ]], [
            'batch' => (object)[
                'entityResultType' => 'FULL',
                'entityResults' => [
                    // Entity with key and properties
                    (object)[
                        'entity' => (object)[
                            'key' => (object)[
                                'path' => [
                                    (object)[
                                        'kind' => 'GrandParent',
                                        'id' => $str_id_grandparent
                                    ],
                                    (object)[
                                        'kind' => 'Parent',
                                        'id' => $str_id_parent
                                    ],
                                    (object)[
                                        'kind' => 'Test',
                                        'id' => $str_id
                                    ]
                                ]
                            ],
                            'properties' => (object)[
                                'name' => (object)[
                                    'excludeFromIndexes' => false,
                                    'stringValue' => 'Tom'
                                ],
                                'age' => (object)[
                                    'excludeFromIndexes' => false,
                                    'integerValue' => 37
                                ]


                            ]
                        ],
                        'version' => '123',
                        'cursor' => 'gfuh37f86gyu23'
                    ]
                ]
            ]
        ]);
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);

        $obj_store = new \GDS\Store('Test', $obj_gateway);
        $obj_entity = $obj_store->fetchOne("SELECT * FROM Test");

        $this->assertInstanceOf('\\GDS\\Entity', $obj_entity);
        $this->assertEquals($str_id, $obj_entity->getKeyId());
        $this->assertEquals('Tom', $obj_entity->name);
        $this->assertEquals(37, $obj_entity->age);

        // Do we have ancestry?
        $this->assertTrue(is_array($obj_entity->getAncestry()));
        $this->assertEquals(2, count($obj_entity->getAncestry()));

        // Extract the ancestry
        $arr_ancestry = $obj_entity->getAncestry();
        $arr_grandparent = $arr_ancestry[0];
        $arr_parent = $arr_ancestry[1];

        // Grandparent tests
        $this->assertArrayHasKey('kind', $arr_grandparent);
        $this->assertEquals('GrandParent', $arr_grandparent['kind']);
        $this->assertArrayHasKey('id', $arr_grandparent);
        $this->assertEquals($str_id_grandparent, $arr_grandparent['id']);

        // Parent test
        $this->assertArrayHasKey('kind', $arr_parent);
        $this->assertEquals('Parent', $arr_parent['kind']);
        $this->assertArrayHasKey('id', $arr_parent);
        $this->assertEquals($str_id_parent, $arr_parent['id']);

        $this->validateHttpClient($obj_http);
    }


    /**
     * Test extraction of end cursors
     */
    public function testEndCursorExtract()
    {
        $str_end_cursor = 'gh3iu4reirh23j4ertiguhn34jetrihue';
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:runQuery', ['json' => (object)[
            'gqlQuery' => (object)[
                'allowLiterals' => true,
                'queryString' => 'SELECT * FROM `Test` ORDER BY __key__ ASC LIMIT @intPageSize ',
                'namedBindings' => (object)[
                    'intPageSize' => (object)[
                        'value' => (object)[
                            'integerValue' => 50
                        ]
                    ],
                ]
            ],
            'partitionId' => (object)[
                'projectId' => self::TEST_PROJECT
            ]

        ]], [
            'batch' => (object)[
                'entityResultType' => 'FULL',
                'entityResults' => [
                    // Entity with key and properties
                    (object)[
                        'entity' => (object)[
                            'key' => (object)[
                                'path' => [
                                    (object)[
                                        'kind' => 'Test',
                                        'id' => '123123123123'
                                    ]
                                ]
                            ],
                            'properties' => (object)[
                                'name' => (object)[
                                    'excludeFromIndexes' => false,
                                    'stringValue' => 'Tom'
                                ],
                                'age' => (object)[
                                    'excludeFromIndexes' => false,
                                    'integerValue' => 37
                                ]


                            ]
                        ],
                        'version' => '123',
                        'cursor' => 'gfuh37f86gyu23'
                    ]
                ],
                'endCursor' => $str_end_cursor
            ]
        ]);
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);

        $obj_store = new \GDS\Store('Test', $obj_gateway);
        $arr_ents = $obj_store->fetchPage(50);

        $this->assertTrue(is_array($arr_ents));

        // End cursor?
        $this->assertEquals($str_end_cursor, $obj_store->getCursor());

        $this->validateHttpClient($obj_http);
    }

    /**
     * Run a complex GQL Query
     */
    public function testGqlQueryParams()
    {
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:runQuery', ['json' => (object)[
            'gqlQuery' => (object)[
                'allowLiterals' => true,
                'queryString' => 'SELECT * FROM Test WHERE booly = @booly AND stringy = @stringy AND inty = @inty AND floaty = @floaty AND datey = @datey AND somekey = @somekey LIMIT 1',
                'namedBindings' => (object)[
                    'booly' => (object)[
                        'value' => (object)[
                            'booleanValue' => true
                        ]
                    ],
                    'stringy' => (object)[
                        'value' => (object)[
                            'stringValue' => 'test'
                        ]
                    ],
                    'inty' => (object)[
                        'value' => (object)[
                            'integerValue' => 123
                        ]
                    ],
                    'floaty' => (object)[
                        'value' => (object)[
                            'doubleValue' => 4.56
                        ]
                    ],
                    'datey' => (object)[
                        'value' => (object)[
                            'timestampValue' => '1955-11-10T01:02:03.000000Z'
                        ]
                    ],
                    'somekey' => (object)[
                        'value' => (object)[
                            'keyValue' => (object)[
                                'path' => [
                                    (object)[
                                        'kind' => 'Test',
                                        'name' => 'my-first-key-name'
                                    ]
                                ],
                                'partitionId' => (object)[
                                    'projectId' => self::TEST_PROJECT
                                ]
                            ]
                        ]
                    ],
                ]
            ],
            'partitionId' => (object)[
                'projectId' => self::TEST_PROJECT
            ]

        ]], [
            'batch' => (object)[
                'entityResultType' => 'FULL',
                'entityResults' => [
                    // Not required for this test
                ]
            ]
        ]);
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);
        $obj_store = new \GDS\Store('Test', $obj_gateway);

        $obj_key_entity = $obj_store->createEntity()->setKeyName('my-first-key-name');
        $obj_store->fetchOne("SELECT * FROM Test WHERE booly = @booly AND stringy = @stringy AND inty = @inty AND floaty = @floaty AND datey = @datey AND somekey = @somekey", [
            'booly' => true,
            'stringy' => 'test',
            'inty' => 123,
            'floaty' => 4.56,
            'datey' => new DateTime('1955-11-10 01:02:03'),
            'somekey' => $obj_key_entity
        ]);

        $this->validateHttpClient($obj_http);
    }

    /**
     * Ensure we throw exceptions for incorrect transaction usage
     *
     * @expectedException        \Exception
     * @expectedExceptionMessage Cross group transactions not supported over REST API v1
     */
    public function testCrossGroupTransactionFails()
    {
        $str_txn_ref = 'txn-string-here';
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:beginTransaction', [], ['transaction' => $str_txn_ref]);
        /** @var \GDS\Gateway\RESTv1 $obj_gateway */
        $obj_gateway = $this->initTestGateway()->setHttpClient($obj_http);
        $obj_gateway->beginTransaction(true);
    }
}