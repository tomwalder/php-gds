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
 * Tests for Protocol Buffer Transactions
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class ProtoBufTransactionTest extends GDSTest {

    /**
     * Start transaction
     */
    public function testBeginTransactionBasic()
    {
        $obj_request = new \google\appengine\datastore\v4\BeginTransactionRequest();
        $obj_response = new \google\appengine\datastore\v4\BeginTransactionResponse();
        $obj_response->setTransaction('test-txn-ref-123');
        $this->apiProxyMock->expectCall('datastore_v4', 'BeginTransaction', $obj_request, $obj_response);
        $_SERVER['APPLICATION_ID'] = 'DatasetTest';
        $obj_gateway = $this->getMockBuilder('\\GDS\\Gateway\\ProtoBuf')->setMethods(['withTransaction','fetchById'])->getMock();
        $obj_gateway->expects($this->once())->method('withTransaction')->with(
            $this->equalTo('test-txn-ref-123')
        )->willReturn($obj_gateway);
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->beginTransaction();
        $obj_store->fetchById('123456');
        $this->apiProxyMock->verify();
    }

    /**
     * Start cross-group transaction
     */
    public function testBeginCrossGroupTransaction()
    {
        $obj_request = new \google\appengine\datastore\v4\BeginTransactionRequest();
        $obj_request->setCrossGroup(TRUE);
        $this->apiProxyMock->expectCall('datastore_v4', 'BeginTransaction', $obj_request, new \google\appengine\datastore\v4\BeginTransactionResponse());
        $this->createBasicStore()->beginTransaction(TRUE);
        $this->apiProxyMock->verify();
    }

}
