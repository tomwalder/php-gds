<?php
/**
 * Copyright 2018 Robert Settle
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
 * Tests for Protocol Buffer v4 Mapper
 *
 * @author Robert Settle <robert.settle@gear4music.com>
 */
class ProtoBufMapperTest extends \PHPUnit_Framework_TestCase
{

    public function testPropertiesMapFromGoogle()
    {
        $obj_schema = (new \GDS\Schema('Child'))
            ->addString('name', true);
        $obj_mapper = new \GDS\Mapper\ProtoBuf();
        $obj_mapper->setSchema($obj_schema);
    
        $obj_response = $this->getPersonResponse();
        $obj_gds_entity = $obj_mapper->mapOneFromResult($obj_response->getFoundList()[0]);
        
        $this->assertTrue(isset($obj_gds_entity->name));
        $this->assertEquals('Tom', $obj_gds_entity->name);

        $this->assertTrue(isset($obj_gds_entity->age));
        $this->assertEquals(36, $obj_gds_entity->age);
        
        $this->assertTrue(isset($obj_gds_entity->dob));
        $this->assertEquals(new DateTime('1979-07-04 08:30:00'), $obj_gds_entity->dob);
        
        $this->assertTrue(isset($obj_gds_entity->weight));
        $this->assertEquals(94.5, $obj_gds_entity->weight);

        $this->assertTrue(isset($obj_gds_entity->lives));
        $this->assertObjectHasAttribute('flt_lat', $obj_gds_entity->lives);
        $this->assertObjectHasAttribute('flt_lon', $obj_gds_entity->lives);
        $this->assertEquals(1.23, $obj_gds_entity->lives->getLatitude());
        $this->assertEquals(4.56, $obj_gds_entity->lives->getLongitude());
    }
    
    public function testDateTimesMapFromGoogle()
    {
        $obj_schema = (new \GDS\Schema('Child'))
            ->addString('name', true);
        $obj_mapper = new \GDS\Mapper\ProtoBuf();
        $obj_mapper->setSchema($obj_schema);
    
        $obj_response = $this->getPersonResponse();
        
        date_default_timezone_set('UTC');
        $obj_gds_entity = $obj_mapper->mapOneFromResult($obj_response->getFoundList()[0]);
        $dtm_comparison = new DateTime('1979-07-04 08:30:00');
        $this->assertTrue(isset($obj_gds_entity->dob));
        $this->assertEquals($dtm_comparison, $obj_gds_entity->dob);
    
        date_default_timezone_set('Europe/London');
        $obj_gds_entity = $obj_mapper->mapOneFromResult($obj_response->getFoundList()[0]);
        $dtm_comparison = new DateTime('1979-07-04 09:30:00');
        $this->assertTrue(isset($obj_gds_entity->dob));
        $this->assertEquals($dtm_comparison, $obj_gds_entity->dob);
        
        date_default_timezone_set('Europe/Moscow');
        $obj_gds_entity = $obj_mapper->mapOneFromResult($obj_response->getFoundList()[0]);
        $dtm_comparison = new DateTime('1979-07-04 11:30:00');
        $this->assertTrue(isset($obj_gds_entity->dob));
        $this->assertEquals($dtm_comparison, $obj_gds_entity->dob);
    }
    
    public function testDateTimeZonesMapFromGoogle()
    {
        $obj_schema = (new \GDS\Schema('Child'))
            ->addString('name', true);
        $obj_mapper = new \GDS\Mapper\ProtoBuf();
        $obj_mapper->setSchema($obj_schema);
        
        $obj_response = $this->getPersonResponse();
        
        date_default_timezone_set('UTC');
        $obj_gds_entity = $obj_mapper->mapOneFromResult($obj_response->getFoundList()[0]);
        $dtm_comparison = new DateTime('1979-07-04 08:30:00');
        $this->assertTrue(isset($obj_gds_entity->dob));
        $this->assertEquals($dtm_comparison->getOffset(), $obj_gds_entity->dob->getOffset());
        
        date_default_timezone_set('Europe/London');
        $obj_gds_entity = $obj_mapper->mapOneFromResult($obj_response->getFoundList()[0]);
        $dtm_comparison = new DateTime('1979-07-04 09:30:00');
        $this->assertTrue(isset($obj_gds_entity->dob));
        $this->assertEquals($dtm_comparison->getOffset(), $obj_gds_entity->dob->getOffset());
        
        date_default_timezone_set('Europe/Moscow');
        $obj_gds_entity = $obj_mapper->mapOneFromResult($obj_response->getFoundList()[0]);
        $dtm_comparison = new DateTime('1979-07-04 11:30:00');
        $this->assertTrue(isset($obj_gds_entity->dob));
        $this->assertEquals($dtm_comparison->getOffset(), $obj_gds_entity->dob->getOffset());
    }
    
    /**
     * Build and return a person response for re-use in multiple tests
     *
     * @return \google\appengine\datastore\v4\LookupResponse
     */
    private function getPersonResponse()
    {
        $obj_response = new \google\appengine\datastore\v4\LookupResponse();
        $obj_found = $obj_response->addFound();
        $obj_entity = $obj_found->mutableEntity();
        $obj_result_key = $obj_entity->mutableKey();
        $obj_result_kpe = $obj_result_key->addPathElement();
        $obj_result_kpe->setKind('Person');
        $obj_result_kpe->setId(123456789);
        $obj_entity->addProperty()->setName('name')->mutableValue()->setIndexed(TRUE)->setStringValue('Tom');
        $obj_entity->addProperty()->setName('age')->mutableValue()->setIndexed(TRUE)->setIntegerValue(36);
        $obj_entity->addProperty()->setName('dob')->mutableValue()->setIndexed(TRUE)->setTimestampMicrosecondsValue(299925000000000);
        $obj_entity->addProperty()->setName('weight')->mutableValue()->setIndexed(TRUE)->setDoubleValue(94.50);
        $obj_entity->addProperty()->setName('likes_php')->mutableValue()->setIndexed(TRUE)->setBooleanValue(TRUE);
        $obj_entity->addProperty()->setName('lives')->mutableValue()->setIndexed(TRUE)->mutableGeoPointValue()->setLatitude(1.23)->setLongitude(4.56);
        return $obj_response;
    }

}