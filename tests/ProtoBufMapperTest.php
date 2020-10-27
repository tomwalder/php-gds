<?php

/**
 * Copyright 2020 Tom Walder
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
 * Tests for ProtoBuf Mapper
 *
 * @author Tom Walder <twalder@gmail.com>
 */
class ProtoBufMapperTest extends \PHPUnit_Framework_TestCase {

    /**
     * Additional test for timestamp microsecond handling
     */
    public function testDateTimeMapToGoogle()
    {
        $obj_schema = (new \GDS\Schema('Person'))->addDatetime('retirement');

        $obj_mapper = new \GDS\Mapper\ProtoBuf();
        $obj_mapper->setSchema($obj_schema);

        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setSchema($obj_schema);
        $obj_gds_entity->setKind('Person');

        $obj_gds_entity->dob = new DateTime('1979-02-05 08:30:00');
        $obj_gds_entity->exact = new DateTime('1979-02-05T08:30:00.12345678Z');
        $obj_gds_entity->retirement = '2050-01-01 09:00:00';

        $obj_target_ent = new \google\appengine\datastore\v4\Entity();
        $obj_mapper->mapToGoogle($obj_gds_entity, $obj_target_ent);

        /** @var \google\appengine\datastore\v4\Property[] $arr_properties */
        $arr_properties = $obj_target_ent->getPropertyList();
        $this->assertTrue(is_array($arr_properties));
        $this->assertCount(3, $arr_properties);

        $arr_props_by_name = [];
        foreach($arr_properties as $obj_prop) {
            $arr_props_by_name[$obj_prop->getName()] = $obj_prop;
        }

        $this->assertArrayHasKey('dob', $arr_props_by_name);
        $obj_dtm_value = $arr_props_by_name['dob']->getValue();
        $this->assertTrue($obj_dtm_value->hasTimestampMicrosecondsValue());
        // '1979-02-05T08:30:00.000000Z'
        $this->assertEquals('287051400000000', $obj_dtm_value->getTimestampMicrosecondsValue());

        $this->assertArrayHasKey('exact', $arr_props_by_name);
        $obj_dtm_value = $arr_props_by_name['exact']->getValue();
        $this->assertTrue($obj_dtm_value->hasTimestampMicrosecondsValue());
        // '1979-02-05T08:30:00.123457Z' 6 OR 7, depending on PHP version (>= 7.2, cuts not rounds)
        $this->assertTrue(in_array($obj_dtm_value->getTimestampMicrosecondsValue(), [
            '287051400123456', // PHP >= 7.2
            '287051400123457', // PHP up to 7.1
        ]));

        $this->assertArrayHasKey('retirement', $arr_props_by_name);
        $obj_dtm_value = $arr_props_by_name['retirement']->getValue();
        $this->assertTrue($obj_dtm_value->hasTimestampMicrosecondsValue());
        // '2050-01-01T09:00:00.000000Z'
        $this->assertEquals('2524640400000000', $obj_dtm_value->getTimestampMicrosecondsValue());
    }
}
