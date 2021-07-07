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
 * @author Tom Walder <twalder@gmail.com>
 */
class RESTv1MapperTest extends \PHPUnit\Framework\TestCase
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
        $obj_schema = (new \GDS\Schema('Person'))->addDatetime('retirement');

        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->setSchema($obj_schema);

        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setSchema($obj_schema);
        $obj_gds_entity->setKind('Person');

        $obj_gds_entity->dob = new DateTime('1979-02-05 08:30:00');
        $obj_gds_entity->exact = new DateTime('1979-02-05T08:30:00.12345678Z');
        $obj_gds_entity->retirement = '2050-01-01 09:00:00';

        $obj_rest_entity = $obj_mapper->mapToGoogle($obj_gds_entity);

        $this->assertObjectHasAttribute('dob', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('timestampValue', $obj_rest_entity->properties->dob);
        $this->assertEquals('1979-02-05T08:30:00.000000Z', $obj_rest_entity->properties->dob->timestampValue);

        $this->assertObjectHasAttribute('exact', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('timestampValue', $obj_rest_entity->properties->exact);

        // '1979-02-05T08:30:00.123457Z' 6 OR 7, depending on PHP version (>= 7.2, cuts not rounds)
        $this->assertTrue(in_array($obj_rest_entity->properties->exact->timestampValue, [
            '1979-02-05T08:30:00.123456Z', // PHP >= 7.2
            '1979-02-05T08:30:00.123457Z', // PHP up to 7.1
        ]));

        $this->assertObjectHasAttribute('retirement', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('timestampValue', $obj_rest_entity->properties->retirement);
        $this->assertEquals('2050-01-01T09:00:00.000000Z', $obj_rest_entity->properties->retirement->timestampValue);

    }

    /**
     * Test data going into Datastore has been correctly converted to UTC when operating in another TZ
     */
    public function testDateTimeMapToGoogleWithTimezone()
    {
        // Let's use a timezone with no Daylight savings
        // This is -03:00 hours
        $str_existing_tz = date_default_timezone_get();
        date_default_timezone_set('America/Cayenne');

        $obj_schema = (new \GDS\Schema('Person'))->addDatetime('retirement');

        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->setSchema($obj_schema);

        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setSchema($obj_schema);
        $obj_gds_entity->setKind('Person');

        $obj_gds_entity->zoned = new DateTime('2021-02-04 08:30:00'); // takes on default timezone
        $obj_gds_entity->dob = new DateTime('1979-02-05T08:30:00+09:00'); // timezone specified
        $obj_gds_entity->exact = new DateTime('1979-02-05T08:30:00.12345678Z'); // UTC assumed
        $obj_gds_entity->ts = new DateTime('@946684800'); // UTC assumed
        $obj_gds_entity->retirement = '2050-01-01 09:00:00'; // takes on default timezone

        $obj_rest_entity = $obj_mapper->mapToGoogle($obj_gds_entity);

        $this->assertEquals(
            '2021-02-04T11:30:00.000000Z',
            $obj_rest_entity->properties->zoned->timestampValue,
            '08:30 (-3) => 11:30 UTC'
        );

        // 1979-02-05T08:30:00+09:00 => previous day, 23:30
        $this->assertEquals(
            '1979-02-04T23:30:00.000000Z',
            $obj_rest_entity->properties->dob->timestampValue,
            'Previous day, 23:30'
        );

        // '1979-02-05T08:30:00.123457Z' 6 OR 7, depending on PHP version (>= 7.2, cuts not rounds)
        $this->assertTrue(in_array($obj_rest_entity->properties->exact->timestampValue, [
            '1979-02-05T08:30:00.123456Z', // PHP >= 7.2
            '1979-02-05T08:30:00.123457Z', // PHP up to 7.1
        ]));

        //
        $this->assertEquals(
            '2050-01-01T12:00:00.000000Z',
            $obj_rest_entity->properties->retirement->timestampValue,
            '-3 hours from Y-m-d H:i:s'
        );

        // Reset the timezone
        date_default_timezone_set($str_existing_tz);
    }

    /**
     * Ensure arrays of lat/lon pairs are supported for geopoints
     */
    public function testGeopointFromArrayData()
    {
        $obj_schema = (new \GDS\Schema('Pub'))->addString('name')->addGeopoint('where');

        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->setSchema($obj_schema);

        $str_name = 'The Fox and Pig and Dog and Wolf and Fiddle and Whistle and Cock';
        $flt_lat = 1.23;
        $flt_lon = 4.56;

        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setSchema($obj_schema);
        $obj_gds_entity->setKind('Pub');
        $obj_gds_entity->name = $str_name;
        $obj_gds_entity->where = [$flt_lat, $flt_lon];

        $obj_rest_entity = $obj_mapper->mapToGoogle($obj_gds_entity);

        $this->assertObjectHasAttribute('name', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('stringValue', $obj_rest_entity->properties->name);
        $this->assertEquals($str_name, $obj_rest_entity->properties->name->stringValue);

        $this->assertObjectHasAttribute('where', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('geoPointValue', $obj_rest_entity->properties->where);
        $this->assertObjectHasAttribute('latitude', $obj_rest_entity->properties->where->geoPointValue);
        $this->assertObjectHasAttribute('longitude', $obj_rest_entity->properties->where->geoPointValue);
        $this->assertEquals(1.23, $obj_rest_entity->properties->where->geoPointValue->latitude);
        $this->assertEquals(4.56, $obj_rest_entity->properties->where->geoPointValue->longitude);
    }

    /**
     * Ensure we throw exceptions for incorrect geopoint data
     *
     */
    public function testGeopointFails()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Geopoint property data not supported: string');

        $obj_schema = (new \GDS\Schema('Pub'))->addString('name')->addGeopoint('where');

        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->setSchema($obj_schema);

        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setSchema($obj_schema);
        $obj_gds_entity->setKind('Pub');
        $obj_gds_entity->name = 'The Fox and Pig and Dog and Wolf and Fiddle and Whistle and Cock';
        $obj_gds_entity->where = 'Not a geopoint value';

        $obj_mapper->mapToGoogle($obj_gds_entity);
    }

    /**
     * Broken mapper request, type 1
     */
    public function testBrokenMapperTypeOne()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Could not build full key path, no Schema set on Mapper and no Kind set on Entity');

        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->buildKeyPath(new \GDS\Entity(), true);
    }

    /**
     * Broken mapper request, type 2
     */
    public function testBrokenMapperTypeTwo()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Could not build full key path, no Kind set on (nth node) GDS Entity');

        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->buildKeyPath(new \GDS\Entity(), false);
    }

    /**
     * Ensure we can control index exclusion
     */
    public function testIndexExclusion()
    {
        $obj_schema = (new \GDS\Schema('Pub'))->addString('name', true)->addString('brewer', false);

        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->setSchema($obj_schema);

        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setSchema($obj_schema);
        $obj_gds_entity->setKind('Pub');
        $obj_gds_entity->name = 'The George';
        $obj_gds_entity->brewer = 'Old Brewery Limited';

        $obj_rest_entity = $obj_mapper->mapToGoogle($obj_gds_entity);

        $this->assertObjectHasAttribute('name', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('stringValue', $obj_rest_entity->properties->name);
        $this->assertObjectHasAttribute('excludeFromIndexes', $obj_rest_entity->properties->name);
        $this->assertFalse($obj_rest_entity->properties->name->excludeFromIndexes, 'name not indexed?');

        $this->assertObjectHasAttribute('brewer', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('stringValue', $obj_rest_entity->properties->brewer);
        $this->assertObjectHasAttribute('excludeFromIndexes', $obj_rest_entity->properties->brewer);
        $this->assertTrue($obj_rest_entity->properties->brewer->excludeFromIndexes, 'brewer indexed?');

    }

    /**
     *
     */
    public function testStringListIndexed()
    {
        $obj_schema = (new \GDS\Schema('Pub'))
            ->addString('name', true)
            ->addStringList('beers', true)
        ;

        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->setSchema($obj_schema);

        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setSchema($obj_schema);
        $obj_gds_entity->setKind('Pub');
        $obj_gds_entity->name = 'The George';
        $obj_gds_entity->beers = ['Doom Bar', 'Punk IPA'];

        $obj_rest_entity = $obj_mapper->mapToGoogle($obj_gds_entity);

        $this->assertObjectHasAttribute('name', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('stringValue', $obj_rest_entity->properties->name);
        $this->assertObjectHasAttribute('excludeFromIndexes', $obj_rest_entity->properties->name);
        $this->assertFalse($obj_rest_entity->properties->name->excludeFromIndexes, 'name not indexed?');

        $this->assertObjectHasAttribute('beers', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('arrayValue', $obj_rest_entity->properties->beers);
        $this->assertObjectNotHasAttribute('excludeFromIndexes', $obj_rest_entity->properties->beers);

        $this->assertObjectHasAttribute('values', $obj_rest_entity->properties->beers->arrayValue);

        $this->assertEquals(2, count($obj_rest_entity->properties->beers->arrayValue->values));

        $obj_first_beer_val = $obj_rest_entity->properties->beers->arrayValue->values[0];

        $this->assertObjectHasAttribute('stringValue', $obj_first_beer_val);
        $this->assertObjectHasAttribute('excludeFromIndexes', $obj_first_beer_val);

        $this->assertFalse($obj_first_beer_val->excludeFromIndexes, 'beer[0] indexed?');
    }

    /**
     *
     */
    public function testStringListNotIndexed()
    {
        $obj_schema = (new \GDS\Schema('Pub'))
            ->addString('name', true)
            ->addStringList('beers', false)
        ;

        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->setSchema($obj_schema);

        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setSchema($obj_schema);
        $obj_gds_entity->setKind('Pub');
        $obj_gds_entity->name = 'The George';
        $obj_gds_entity->beers = ['Doom Bar', 'Punk IPA'];

        $obj_rest_entity = $obj_mapper->mapToGoogle($obj_gds_entity);

        $this->assertObjectHasAttribute('name', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('stringValue', $obj_rest_entity->properties->name);
        $this->assertObjectHasAttribute('excludeFromIndexes', $obj_rest_entity->properties->name);
        $this->assertFalse($obj_rest_entity->properties->name->excludeFromIndexes, 'name not indexed?');

        $this->assertObjectHasAttribute('beers', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('arrayValue', $obj_rest_entity->properties->beers);
        $this->assertObjectNotHasAttribute('excludeFromIndexes', $obj_rest_entity->properties->beers);

        $this->assertObjectHasAttribute('values', $obj_rest_entity->properties->beers->arrayValue);

        $this->assertEquals(2, count($obj_rest_entity->properties->beers->arrayValue->values));

        $obj_first_beer_val = $obj_rest_entity->properties->beers->arrayValue->values[0];

        $this->assertObjectHasAttribute('stringValue', $obj_first_beer_val);
        $this->assertObjectHasAttribute('excludeFromIndexes', $obj_first_beer_val);

        $this->assertTrue($obj_first_beer_val->excludeFromIndexes, 'beer[0] indexed?');
    }

    /**
     * Tests 2 tiers of ancestry, based on entity
     */
    public function testAncestryFromEntity()
    {

        $obj_schema = (new \GDS\Schema('Child'))->addString('name', true);
        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->setSchema($obj_schema);

        $obj_gds_grandparent = new \GDS\Entity();
        $obj_gds_grandparent->setKind('GrandParent');
        $obj_gds_grandparent->setKeyId('123456781');
        $obj_gds_grandparent->name = 'Grandfather';

        $obj_gds_parent = new \GDS\Entity();
        $obj_gds_parent->setKind('Parent');
        $obj_gds_parent->setKeyId('123456782');
        $obj_gds_parent->name = 'Dad';
        $obj_gds_parent->setAncestry($obj_gds_grandparent);

        $obj_gds_child = new \GDS\Entity();
        $obj_gds_child->setKind('Child');
        $obj_gds_child->name = 'Son';
        $obj_gds_child->setAncestry($obj_gds_parent);

        $obj_rest_entity = $obj_mapper->mapToGoogle($obj_gds_child);

        $this->assertObjectHasAttribute('name', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('stringValue', $obj_rest_entity->properties->name);
        $this->assertEquals('Son', $obj_rest_entity->properties->name->stringValue);

        $this->assertObjectHasAttribute('key', $obj_rest_entity);
        $this->assertObjectHasAttribute('path', $obj_rest_entity->key);
        $this->assertTrue(is_array($obj_rest_entity->key->path));
        $this->assertEquals(3, count($obj_rest_entity->key->path));

        $obj_path_first = $obj_rest_entity->key->path[0];
        $obj_path_second = $obj_rest_entity->key->path[1];
        $obj_path_last = $obj_rest_entity->key->path[2];

        $this->assertObjectHasAttribute('kind', $obj_path_first);
        $this->assertObjectHasAttribute('id', $obj_path_first);
        $this->assertObjectNotHasAttribute('name', $obj_path_first);
        $this->assertEquals('GrandParent', $obj_path_first->kind);
        $this->assertEquals('123456781', $obj_path_first->id);

        $this->assertObjectHasAttribute('kind', $obj_path_second);
        $this->assertObjectHasAttribute('id', $obj_path_second);
        $this->assertObjectNotHasAttribute('name', $obj_path_second);
        $this->assertEquals('Parent', $obj_path_second->kind);
        $this->assertEquals('123456782', $obj_path_second->id);

        $this->assertObjectHasAttribute('kind', $obj_path_last);
        $this->assertObjectNotHasAttribute('id', $obj_path_last);
        $this->assertObjectNotHasAttribute('name', $obj_path_last);
        $this->assertEquals('Child', $obj_path_last->kind);

    }

    /**
     * Tests 2 tiers of ancestry, based on array
     */
    public function testAncestryFromArray()
    {
        $obj_schema = (new \GDS\Schema('Child'))->addString('name', true);
        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->setSchema($obj_schema);

        $obj_gds_child = new \GDS\Entity();
        $obj_gds_child->setKind('Child');
        $obj_gds_child->name = 'Son';
        $obj_gds_child->setAncestry([
            [
                'kind' => 'GrandParent',
                'id' => '123456781'
            ], [
                'kind' => 'Parent',
                'id' => '123456782'
            ]
        ]);

        $obj_rest_entity = $obj_mapper->mapToGoogle($obj_gds_child);

        $this->assertObjectHasAttribute('name', $obj_rest_entity->properties);
        $this->assertObjectHasAttribute('stringValue', $obj_rest_entity->properties->name);
        $this->assertEquals('Son', $obj_rest_entity->properties->name->stringValue);

        $this->assertObjectHasAttribute('key', $obj_rest_entity);
        $this->assertObjectHasAttribute('path', $obj_rest_entity->key);
        $this->assertTrue(is_array($obj_rest_entity->key->path));
        $this->assertEquals(3, count($obj_rest_entity->key->path));

        $obj_path_first = $obj_rest_entity->key->path[0];
        $obj_path_second = $obj_rest_entity->key->path[1];
        $obj_path_last = $obj_rest_entity->key->path[2];

        $this->assertObjectHasAttribute('kind', $obj_path_first);
        $this->assertObjectHasAttribute('id', $obj_path_first);
        $this->assertObjectNotHasAttribute('name', $obj_path_first);
        $this->assertEquals('GrandParent', $obj_path_first->kind);
        $this->assertEquals('123456781', $obj_path_first->id);

        $this->assertObjectHasAttribute('kind', $obj_path_second);
        $this->assertObjectHasAttribute('id', $obj_path_second);
        $this->assertObjectNotHasAttribute('name', $obj_path_second);
        $this->assertEquals('Parent', $obj_path_second->kind);
        $this->assertEquals('123456782', $obj_path_second->id);

        $this->assertObjectHasAttribute('kind', $obj_path_last);
        $this->assertObjectNotHasAttribute('id', $obj_path_last);
        $this->assertObjectNotHasAttribute('name', $obj_path_last);
        $this->assertEquals('Child', $obj_path_last->kind);
    }

    /**
     * Confirm we correctly extract DateTime objects from REST responses
     *
     * @throws Exception
     */
    public function testMapDatetimeFromGoogle()
    {
        $obj_schema = (new \GDS\Schema('Event'))->addDatetime('when');
        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->setSchema($obj_schema);
        $obj_entity = $obj_mapper->mapOneFromResult($this->buildFakeResponse());
        $this->assertInstanceOf('\\DateTime', $obj_entity->when);
        $this->assertInstanceOf('\\DateTime', $obj_entity->then);
        $str_php_micros = '1412262083.045123';
        $this->assertEquals($str_php_micros, $obj_entity->when->format(\GDS\Mapper::DATETIME_FORMAT_UDOTU));
        $this->assertEquals('2014-10-02 15:01:23', $obj_entity->when->format('Y-m-d H:i:s'));
        $this->assertEquals('2015-11-03 16:02:24', $obj_entity->then->format('Y-m-d H:i:s'));
    }

    /**
     * Confirm we correctly extract DateTime objects from REST responses
     *
     * @throws Exception
     */
    public function testMapDatetimeFromGoogleInTimezone()
    {
        $str_existing_tz = date_default_timezone_get();
        date_default_timezone_set('America/Cayenne');

        $obj_schema = (new \GDS\Schema('Event'))->addDatetime('when');
        $obj_mapper = new \GDS\Mapper\RESTv1();
        $obj_mapper->setSchema($obj_schema);
        $obj_entity = $obj_mapper->mapOneFromResult($this->buildFakeResponse());
        $this->assertInstanceOf('\\DateTime', $obj_entity->when);
        $this->assertInstanceOf('\\DateTime', $obj_entity->then);
        $str_php_micros = '1412262083.045123';
        $this->assertEquals($str_php_micros, $obj_entity->when->format(\GDS\Mapper::DATETIME_FORMAT_UDOTU));
        $this->assertEquals('2014-10-02 12:01:23', $obj_entity->when->format('Y-m-d H:i:s'));
        $this->assertEquals('2015-11-03 13:02:24', $obj_entity->then->format('Y-m-d H:i:s'));
        $this->assertEquals('America/Cayenne', $obj_entity->when->getTimezone()->getName());
        $this->assertEquals('America/Cayenne', $obj_entity->then->getTimezone()->getName());

        // Reset the timezone
        date_default_timezone_set($str_existing_tz);
    }

    /**
     * Build a fake REST response payload
     *
     * @return stdClass
     */
    private function buildFakeResponse(): \stdClass
    {
        return (object)[
            'entity' => (object) [
                'key' => (object)[
                    "partitionId" => (object)[
                        "projectId" => 'test-project',
                        "namespaceId" => 'test-namespace',
                    ],
                    'path' => [
                        (object)[
                            "kind" => 'Event',
                            "id" => '123456789',
                        ]
                    ]
                ],
                'properties' => (object)[
                    'when' => (object)[
                        "timestampValue" => '2014-10-02T15:01:23.045123456Z',
                    ],
                    'then' => (object)[
                        "timestampValue" => '2015-11-03T16:02:24.055123456Z',
                    ],
                ],
            ]
        ];
    }
}
