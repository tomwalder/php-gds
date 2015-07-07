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
 * Tests for the GQL parser
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class GQLParserTest extends \PHPUnit_Framework_TestCase
{

    public function testBasicKind()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse("SELECT * FROM Person");
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals([], $obj_parser->getOrderBy());
        $this->assertEquals([], $obj_parser->getFilters());
    }

    public function testBacktickKind()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse("SELECT * FROM `Person Of Interest`");
        $this->assertEquals('Person Of Interest', $obj_parser->getKind());
        $this->assertEquals([], $obj_parser->getOrderBy());
        $this->assertEquals([], $obj_parser->getFilters());
    }

    public function testQuotedKind()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM "Some Kind"');
        $this->assertEquals('Some Kind', $obj_parser->getKind());
        $this->assertEquals([], $obj_parser->getOrderBy());
        $this->assertEquals([], $obj_parser->getFilters());
    }

    public function testBasicLimit()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM Person LIMIT 10');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals(10, $obj_parser->getLimit());
        $this->assertEquals([], $obj_parser->getOrderBy());
        $this->assertEquals([], $obj_parser->getFilters());
    }

    public function testBasicOffset()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM Person OFFSET 11');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals(11, $obj_parser->getOffset());
        $this->assertEquals([], $obj_parser->getOrderBy());
        $this->assertEquals([], $obj_parser->getFilters());
    }

    public function testLimitOffset()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM Person LIMIT 10 OFFSET 11');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals(10, $obj_parser->getLimit());
        $this->assertEquals(11, $obj_parser->getOffset());
        $this->assertEquals([], $obj_parser->getOrderBy());
        $this->assertEquals([], $obj_parser->getFilters());
    }

    public function testStartCursorString()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM Person OFFSET "jsdhfkshdfkshfkjsdhfsdk"');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals('jsdhfkshdfkshfkjsdhfsdk', $obj_parser->getStartCursor());
        $this->assertNull($obj_parser->getLimit());
        $this->assertNull($obj_parser->getOffset());
        $this->assertEquals([], $obj_parser->getOrderBy());
        $this->assertEquals([], $obj_parser->getFilters());
    }

    public function testEndCursorString()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM Person LIMIT "jsdhfkshdfkshfkjsdhfsdk" OFFSET 20');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals('jsdhfkshdfkshfkjsdhfsdk', $obj_parser->getEndCursor());
        $this->assertNull($obj_parser->getStartCursor());
        $this->assertNull($obj_parser->getLimit());
        $this->assertEquals(20, $obj_parser->getOffset());
        $this->assertEquals([], $obj_parser->getOrderBy());
        $this->assertEquals([], $obj_parser->getFilters());
    }

    public function testOrderBy()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM Person ORDER BY __key__ ASC');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals([[
            'property' => '__key__',
            'direction' => \google\appengine\datastore\v4\PropertyOrder\Direction::ASCENDING
        ]], $obj_parser->getOrderBy());
    }

    public function testOrderByProperty()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM Person ORDER BY some_property DESC');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals([[
            'property' => 'some_property',
            'direction' => \google\appengine\datastore\v4\PropertyOrder\Direction::DESCENDING
        ]], $obj_parser->getOrderBy());
    }

    public function testOrderByPropertyAsc()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM Person ORDER BY some_property');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals([[
            'property' => 'some_property',
            'direction' => \google\appengine\datastore\v4\PropertyOrder\Direction::ASCENDING
        ]], $obj_parser->getOrderBy());
    }

    public function testBasicSingleWhere()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM Person WHERE some_property = "grey"');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals([[
            'lhs' => 'some_property',
            'op' => google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL,
            'comp' => '=',
            'rhs' => 'grey'
        ]], $obj_parser->getFilters());
    }

    public function testMultiWhere()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM Person WHERE some_property = "grey" AND other_property = 10');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals([[
            'lhs' => 'some_property',
            'op' => google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL,
            'comp' => '=',
            'rhs' => 'grey'
        ],[
            'lhs' => 'other_property',
            'op' => google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL,
            'comp' => '=',
            'rhs' => 10
        ]], $obj_parser->getFilters());
    }


}
/*
SELECT * FROM myKind WHERE myProp >= 100 AND myProp < 200
SELECT * FROM myKind LIMIT 50 OFFSET @startCursor
SELECT * FROM myKind ORDER BY myProp DESC
SELECT * FROM Person WHERE age >= 18 AND age <= 35
SELECT * FROM Person ORDER BY age DESC LIMIT 3
*/