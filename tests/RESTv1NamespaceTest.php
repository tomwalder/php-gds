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
 * Tests for REST API v1 Namespaces
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class RESTv1NamespaceTest extends \RESTv1Test
{

    /**
     * Test upsert with namespace
     */
    public function testUpsertWithNamespace()
    {
        $str_ns = 'SomeNamepsace';
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
                                'projectId' => self::TEST_PROJECT,
                                'namespaceId' => $str_ns
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
        $obj_gateway = $this->initTestGateway($str_ns)->setHttpClient($obj_http);

        $obj_store = new \GDS\Store('Test', $obj_gateway);
        $obj_entity = new GDS\Entity();
        $obj_entity->setKeyId('123456789');
        $obj_entity->name = 'Tom';
        $obj_store->upsert($obj_entity);

        $this->validateHttpClient($obj_http);
    }

    /**
     * Test GQL Fetch With Namespace
     */
    public function testGqlFetchWithNamespace()
    {
        $str_ns = 'Namespacey';
        $str_id = '1263751723';
        $str_id_parent = '1263751724';
        $str_id_grandparent = '1263751725';
        $obj_http = $this->initTestHttpClient('https://datastore.googleapis.com/v1/projects/DatasetTest:runQuery', ['json' => (object)[
            'gqlQuery' => (object)[
                'allowLiterals' => true,
                'queryString' => 'SELECT * FROM Test LIMIT 1'
            ],
            'partitionId' => (object)[
                'projectId' => self::TEST_PROJECT,
                'namespaceId' => $str_ns
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
        $obj_gateway = $this->initTestGateway($str_ns)->setHttpClient($obj_http);

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
     * Test fetch by key with namespace
     */
    public function testFetchByKeyWithNamespace()
    {
        $str_ns = 'SpaceTheFinalFrontier';
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
                        'projectId' => self::TEST_PROJECT,
                        'namespaceId' => $str_ns
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
        $obj_gateway = $this->initTestGateway($str_ns)->setHttpClient($obj_http);

        $obj_store = new \GDS\Store('Test', $obj_gateway);
        $obj_entity = $obj_store->fetchById($str_id);

        $this->assertInstanceOf('\\GDS\\Entity', $obj_entity);
        $this->assertEquals($str_id, $obj_entity->getKeyId());
        $this->assertEquals('Tom', $obj_entity->name);


        $this->validateHttpClient($obj_http);
    }

    /**
     * Test delete with namespace
     */
    public function testDeleteWithNamespace()
    {
        $str_ns = 'TestNameSpace';
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
                            'projectId' => self::TEST_PROJECT,
                            'namespaceId' => $str_ns
                        ]
                    ]
                ]
            ]
        ]]);
        $obj_gateway = $this->initTestGateway($str_ns)->setHttpClient($obj_http);

        $obj_store = new \GDS\Store('Test', $obj_gateway);
        $obj_entity = (new GDS\Entity())->setKeyId('123456789');
        $obj_store->delete([$obj_entity]);

        $this->validateHttpClient($obj_http);
    }

}