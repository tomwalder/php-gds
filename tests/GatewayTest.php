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
 * Tests for Gateway class
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class GatewayTest extends \PHPUnit_Framework_TestCase {

    /**
     * delete
     */
    public function testDelete()
    {
        $obj_entity = new GDS\Entity();
        /** @var \GDS\Gateway $obj_gateway */
        $obj_gateway = $this->getMockBuilder('\\GDS\\Gateway\\ProtoBuf')->setMethods(['deleteMulti'])->setConstructorArgs(['DatasetTest'])->getMock();
        $obj_gateway->expects($this->once())->method('deleteMulti')->with(
            $this->equalTo([$obj_entity])
        );
        $obj_gateway->delete($obj_entity);
    }

    /**
     * put
     */
    public function testPut()
    {
        $obj_entity = new GDS\Entity();
        /** @var \GDS\Gateway $obj_gateway */
        $obj_gateway = $this->getMockBuilder('\\GDS\\Gateway\\ProtoBuf')->setMethods(['putMulti'])->setConstructorArgs(['DatasetTest'])->getMock();
        $obj_gateway->expects($this->once())->method('putMulti')->with(
            $this->equalTo([$obj_entity])
        );
        $obj_gateway->put($obj_entity);
    }


    /**
     * fetchByName
     */
    public function testfetchByName()
    {
        /** @var \GDS\Gateway $obj_gateway */
        $obj_gateway = $this->getMockBuilder('\\GDS\\Gateway\\ProtoBuf')->setMethods(['fetchByNames'])->setConstructorArgs(['DatasetTest'])->getMock();
        $obj_gateway->expects($this->once())->method('fetchByNames')->with(
            $this->equalTo(['test-name'])
        )->willReturn(['correct', 'fail']);
        $mix_result = $obj_gateway->fetchByName('test-name');
        $this->assertEquals('correct', $mix_result);
    }

}