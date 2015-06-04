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
use google\appengine\testing\ApiProxyTestBase;

/**
 * Tests for Protocol Buffer Errors
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class ProtoBufErrorTest extends ApiProxyTestBase {

    /**
     * Missing Dataset
     */
    public function testMissingDataset()
    {
        $obj_ex = NULL;
        try {
            $obj_gateway = new GDS\Gateway\ProtoBuf();
        } catch (\Exception $obj_ex) {}
        $this->assertEquals($obj_ex, new \Exception('Could not determine DATASET, please pass to GDS\Gateway\ProtoBuf::__construct()'));
    }

}
