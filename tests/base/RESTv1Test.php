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
 * Tests for REST API v1
 *
 * @author Tom Walder <tom@docnet.nu>
 */
abstract class RESTv1Test extends \PHPUnit_Framework_TestCase
{

    /**
     * Dataset
     */
    const TEST_PROJECT = 'DatasetTest';

    /**
     * @var string
     */
    private $str_expected_url = null;

    /**
     * @var string
     */
    private $arr_expected_payload = null;

    /**
     * Prepare and return a fake Guzzle HTTP client, so that we can test and simulate requests/responses
     *
     * @param $str_expected_url
     * @param null $arr_expected_payload
     * @param null $obj_response
     * @return FakeGuzzleClient
     */
    protected function initTestHttpClient($str_expected_url, $arr_expected_payload = null, $obj_response = null)
    {
        $this->str_expected_url = $str_expected_url;
        $this->arr_expected_payload = $arr_expected_payload;
        return new FakeGuzzleClient($obj_response);
    }

    /**
     * Build and return a testable Gateway
     *
     * @param null $str_namespace
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function initTestGateway($str_namespace = null)
    {
        return $this->getMockBuilder('\\GDS\\Gateway\\RESTv1')->setMethods(['initHttpClient'])->setConstructorArgs([self::TEST_PROJECT, $str_namespace])->getMock();
    }

    /**
     * Validate URL and Payload
     *
     * @param FakeGuzzleClient $obj_http
     */
    protected function validateHttpClient(\FakeGuzzleClient $obj_http)
    {
        $this->assertEquals($this->str_expected_url, $obj_http->getPostedUrl());
        if(null !== $this->arr_expected_payload) {
            $this->assertEquals($this->arr_expected_payload, $obj_http->getPostedParams());
        }
    }


}