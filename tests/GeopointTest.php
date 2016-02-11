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
 * Tests for Geopoint class
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class GeopointTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     */
    public function testConstruct()
    {
        $obj_gp = new \GDS\Property\Geopoint(1.2, 3.4);
        $this->assertEquals(1.2, $obj_gp->getLatitude());
        $this->assertEquals(3.4, $obj_gp->getLongitude());
    }

    /**
     *
     */
    public function testEmpty()
    {
        $obj_gp = new \GDS\Property\Geopoint();
        $this->assertEquals(0.0, $obj_gp->getLatitude());
        $this->assertEquals(0.0, $obj_gp->getLongitude());
    }

    /**
     *
     */
    public function testSetters()
    {
        $obj_gp = new \GDS\Property\Geopoint();
        $obj_gp->setLatitude(5.6);
        $obj_gp->setLongitude(7.8);
        $this->assertEquals(5.6, $obj_gp->getLatitude());
        $this->assertEquals(7.8, $obj_gp->getLongitude());
    }

    /**
     *
     */
    public function testArrayAccessRead()
    {
        $obj_gp = new \GDS\Property\Geopoint(1.2, 3.4);
        $this->assertEquals(1.2, $obj_gp[0]);
        $this->assertEquals(3.4, $obj_gp[1]);
    }

    /**
     *
     */
    public function testArrayAccessWrite()
    {
        $obj_gp = new \GDS\Property\Geopoint();
        $obj_gp[0] = 2.1;
        $obj_gp[1] = 3.4;
        $this->assertEquals(2.1, $obj_gp->getLatitude());
        $this->assertEquals(3.4, $obj_gp->getLongitude());
    }

    public function testIsset()
    {
        $obj_gp = new \GDS\Property\Geopoint();
        $this->assertTrue(isset($obj_gp[0]));
        $this->assertTrue(isset($obj_gp[1]));
        $this->assertFalse(isset($obj_gp[2]));
    }

    public function testUnset()
    {
        $obj_gp = new \GDS\Property\Geopoint(1.2, 3.4);
        $this->assertEquals(1.2, $obj_gp->getLatitude());
        $this->assertEquals(3.4, $obj_gp->getLongitude());
        unset($obj_gp[0]);
        $this->assertEquals(0.0, $obj_gp->getLatitude());
        $this->assertEquals(3.4, $obj_gp->getLongitude());
        unset($obj_gp[1]);
        $this->assertEquals(0.0, $obj_gp->getLatitude());
        $this->assertEquals(0.0, $obj_gp->getLongitude());
    }

    public function testFailSet()
    {
        $obj_gp = new \GDS\Property\Geopoint();
        $this->setExpectedException('UnexpectedValueException');
        $obj_gp[2] = 1.21;
    }

    public function testFailGet()
    {
        $obj_gp = new \GDS\Property\Geopoint();
        $this->setExpectedException('UnexpectedValueException');
        $int_tmp = $obj_gp[2];
    }

}