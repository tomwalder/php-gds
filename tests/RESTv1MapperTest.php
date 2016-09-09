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
 * Tests for REST API v1 Mapper
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class RESTv1MapperTest extends \PHPUnit_Framework_TestCase
{

    public function testDynamicPropertiesMapToGoogle()
    {
        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setKind('Person');
        $obj_gds_entity->name = 'Tom';
        $obj_gds_entity->age = 37;
        $obj_gds_entity->dob = new DateTime('1979-02-05 08:30:00');
        $obj_gds_entity->weight = 85.91;
        $obj_gds_entity->lives = new \GDS\Property\Geopoint(1.23, 4.56);
        $obj_gds_entity->simple = new \Simple();
        $obj_gds_entity->cares = true;
        $obj_gds_entity->likes = ['beer', 'cycling', 'php'];

        $obj_rest_entity = $obj_mapper->mapToGoogle($obj_gds_entity);

        $this->assertInstanceOf('\\stdClass', $obj_rest_entity->properties);

        $this->assertObjectHasAttribute('name', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('stringValue', $obj_rest_entity->properties->name);
        $this->assertEquals('Tom', $obj_rest_entity->properties->name->stringValue);

        $this->assertObjectHasAttribute('age', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('integerValue', $obj_rest_entity->properties->age);
        $this->assertEquals(37, $obj_rest_entity->properties->age->integerValue);

        $this->assertObjectHasAttribute('dob', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('timestampValue', $obj_rest_entity->properties->dob);
        $this->assertEquals('1979-02-05T08:30:00.000000Z', $obj_rest_entity->properties->dob->timestampValue);

        $this->assertObjectHasAttribute('weight', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('doubleValue', $obj_rest_entity->properties->weight);
        $this->assertEquals(85.91, $obj_rest_entity->properties->weight->doubleValue);

        $this->assertObjectHasAttribute('lives', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('geoPointValue', $obj_rest_entity->properties->lives);
        $this->assertObjectHasAttribute('latitude', $obj_rest_entity->properties->lives->geoPointValue);
        $this->assertObjectHasAttribute('longitude', $obj_rest_entity->properties->lives->geoPointValue);
        $this->assertEquals(1.23, $obj_rest_entity->properties->lives->geoPointValue->latitude);
        $this->assertEquals(4.56, $obj_rest_entity->properties->lives->geoPointValue->longitude);

        $this->assertObjectHasAttribute('simple', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('stringValue', $obj_rest_entity->properties->simple);
        $this->assertEquals('success!', $obj_rest_entity->properties->simple->stringValue);

        $this->assertObjectHasAttribute('cares', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('booleanValue', $obj_rest_entity->properties->cares);
        $this->assertTrue($obj_rest_entity->properties->cares->booleanValue);

        $this->assertObjectHasAttribute('likes', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('arrayValue', $obj_rest_entity->properties->likes);
        $this->assertInstanceOf('\\stdClass', $obj_rest_entity->properties->likes->arrayValue);
        $this->assertObjectHasAttribute('values', $obj_rest_entity->properties->likes->arrayValue);
        $this->assertTrue(is_array($obj_rest_entity->properties->likes->arrayValue->values));
        $arr_string_values = $obj_rest_entity->properties->likes->arrayValue->values;
        $this->assertEquals(3, count($arr_string_values));
        foreach($arr_string_values as $obj_string_value) {
            $this->assertInstanceOf('\\stdClass', $obj_string_value);
            $this->assertObjectHasAttribute('stringValue', $obj_string_value);
        }
    }


    public function testDateTimeMapToGoogle()
    {
        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setKind('Person');

        $obj_gds_entity->dob = new DateTime('1979-02-05 08:30:00');
        $obj_gds_entity->exact = new DateTime('1979-02-05T08:30:00.12345678Z');

        $obj_rest_entity = $obj_mapper->mapToGoogle($obj_gds_entity);

        $this->assertObjectHasAttribute('dob', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('timestampValue', $obj_rest_entity->properties->dob);
        $this->assertEquals('1979-02-05T08:30:00.000000Z', $obj_rest_entity->properties->dob->timestampValue);

        $this->assertObjectHasAttribute('exact', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('timestampValue', $obj_rest_entity->properties->exact);
        $this->assertEquals('1979-02-05T08:30:00.123457Z', $obj_rest_entity->properties->exact->timestampValue);

    }


//    public function testMapToGoogle()
//    {
//        $obj_mapper = new \GDS\Mapper\RESTv1();
//        $obj_gds_entity = new \GDS\Entity();
//        $obj_gds_entity->setKind('Person');
//        $obj_rest_entity = $obj_mapper->mapToGoogle($obj_gds_entity);
//        $this->assertEquals('expected', $obj_rest_entity->actual);
//    }

}