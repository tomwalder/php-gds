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
 * Contains shared code for our tests
 *
 * @author Tom Walder <tom@docnet.nu>
 */
abstract class GDSTest extends ApiProxyTestBase
{
    /**
     * Create a basic Store & Gateway
     *
     * @return \GDS\Store
     */
    protected function createBasicStore()
    {
        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        return $obj_store;
    }
}