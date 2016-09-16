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

    /**
      * @expectedException              \GDS\Exception\GQL
      * @expectedExceptionMessageRegExp /Invalid string representation in: .+/
      */
    public function testWhereStringQuotesMissing()
    {
        $obj_schema = (new GDS\Schema('Person'))
            ->addString('some_property');
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser($obj_schema);
        $obj_parser->parse('SELECT * FROM Person WHERE some_property = grey');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals([[
            'lhs' => 'some_property',
            'op' => google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL,
            'comp' => '=',
            'rhs' => 'grey'
        ]], $obj_parser->getFilters());
    }

    /**
      * Nonexistent properties should run fine because datastore is schemaless
      * #expectedException              \GDS\Exception\GQL
      * #expectedExceptionMessageRegExp /Property doesn't exist: .+/
      */
    public function testWhereNonexistentProperties()
    {
        $obj_schema = (new GDS\Schema('Person'))
            ->addString('some_property');
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser($obj_schema);
        $obj_parser->parse('SELECT * FROM Person WHERE other_property = 10');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals([[
            'lhs' => 'other_property',
            'op' => google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL,
            'comp' => '=',
            'rhs' => 10
        ]], $obj_parser->getFilters());
    }

    /**
     * Ensure that we can only supply a Schema (optionally) to the GQL parser
     */
    public function testNonSchemaTypeCheck()
    {
        $obj_reflection = new \ReflectionClass('\\GDS\\Mapper\\ProtoBufGQLParser');
        $obj_constructor = $obj_reflection->getConstructor();
        $arr_params = $obj_constructor->getParameters();
        $this->assertEquals(\GDS\Schema::class, $arr_params[0]->getClass()->getName());
        $this->assertTrue($arr_params[0]->isOptional());
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

    public function testBasicFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_request->mutableQuery()->addKind()->setName('Book');

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll("SELECT * FROM Book");

        $obj_deny_proxy->verify();
    }

    public function testLimitFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_query = $obj_request->mutableQuery();
        $obj_query->addKind()->setName('Book');
        $obj_query->setLimit(20);

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->query("SELECT * FROM Book")->fetchPage(20);

        $obj_deny_proxy->verify();
    }

    public function testOrderedLimitFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_query = $obj_request->mutableQuery();
        $obj_query->addKind()->setName('Book');
        $obj_query->setLimit(20);
        $obj_query->addOrder()->setDirection(\google\appengine\datastore\v4\PropertyOrder\Direction::DESCENDING)->mutableProperty()->setName('author');

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->query("SELECT * FROM Book ORDER BY author DESC")->fetchPage(20);

        $obj_deny_proxy->verify();
    }

    /**
     * @expectedException        \GDS\Exception\GQL
     * @expectedExceptionMessage Sorry, only 'SELECT *' (full Entity) queries are currently supported by php-gds
     */
    public function testFallbackSelectStartOnly()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_request->mutableQuery()->addKind()->setName('Book');

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll("SELECT name, author FROM Book");

        $obj_deny_proxy->verify();
    }

    /**
     * @expectedException       \InvalidArgumentException
     * @expectedExceptionMessage Unexpected array parameter
     */
    public function testUnsupportedArrayParamFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);
        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll("SELECT * FROM Book WHERE author = @author", ['author' => ['tom', 'dick', 'harry']]);
    }

    /**
     * @expectedException       \InvalidArgumentException
     * @expectedExceptionMessage Unexpected, non-string-able object parameter:
     */
    public function testUnstringableObjectParamFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);
        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll("SELECT * FROM Book WHERE author = @author", ['author' => new \stdClass()]);
    }

    /**
     * @expectedException       \InvalidArgumentException
     * @expectedExceptionMessage Unsupported parameter type: resource
     */
    public function testUnsuportedResourceParamFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);
        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll("SELECT * FROM Book WHERE author = @author", ['author' => fopen('php://input', 'r')]);
    }

    public function testStringParamFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_query = $obj_request->mutableQuery();
        $obj_query->addKind()->setName('Book');
        $obj_prop_filter = $obj_query->mutableFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter->mutableProperty()->setName('author');
        $obj_prop_filter->mutableValue()->setStringValue('William Shakespeare');

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll("SELECT * FROM Book WHERE author = @author", ['author' => 'William Shakespeare']);

        $obj_deny_proxy->verify();
    }

    public function testStringifyParamFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_query = $obj_request->mutableQuery();
        $obj_query->addKind()->setName('Book');
        $obj_prop_filter = $obj_query->mutableFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter->mutableProperty()->setName('author');
        $obj_prop_filter->mutableValue()->setStringValue('success!');

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll("SELECT * FROM Book WHERE author = @author", ['author' => new Simple()]);

        $obj_deny_proxy->verify();
    }

    public function testComplexParamFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_query = $obj_request->mutableQuery();
        $obj_query->addKind()->setName('Book');

        $obj_comp_filter = $obj_query->mutableFilter()->mutableCompositeFilter()->setOperator(\google\appengine\datastore\v4\CompositeFilter\Operator::AND_);

        $obj_prop_filter1 = $obj_comp_filter->addFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter1->mutableProperty()->setName('author');
        $obj_prop_filter1->mutableValue()->setStringValue('William Shakespeare');

        $obj_prop_filter2 = $obj_comp_filter->addFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter2->mutableProperty()->setName('lent');
        $obj_prop_filter2->mutableValue()->setBooleanValue(true);

        $obj_prop_filter3 = $obj_comp_filter->addFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::GREATER_THAN);
        $obj_prop_filter3->mutableProperty()->setName('age');
        $obj_prop_filter3->mutableValue()->setIntegerValue(72);

        $obj_prop_filter3 = $obj_comp_filter->addFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter3->mutableProperty()->setName('gigawatts');
        $obj_prop_filter3->mutableValue()->setDoubleValue(1.21);

        $obj_prop_filter4 = $obj_comp_filter->addFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::LESS_THAN);
        $obj_prop_filter4->mutableProperty()->setName('when');
        $obj_prop_filter4->mutableValue()->setTimestampMicrosecondsValue(286965000000000);

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll("SELECT * FROM Book WHERE author = @author AND lent = @lent AND age > @age AND gigawatts = @gw AND when < @then", [
            'author' => 'William Shakespeare',
            'lent' => true,
            'age' => 72,
            'gw' => 1.21,
            'then' => new \DateTime('1979-02-04 08:30:00')
        ]);

        $obj_deny_proxy->verify();
    }

    /**
     * https://github.com/tomwalder/php-gds/issues/114
     */
    public function testSchemaEval()
    {

        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_query = $obj_request->mutableQuery();
        $obj_query->addKind()->setName('Service');

        $obj_comp_filter = $obj_query->mutableFilter()->mutableCompositeFilter()->setOperator(\google\appengine\datastore\v4\CompositeFilter\Operator::AND_);

        $obj_prop_filter1 = $obj_comp_filter->addFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter1->mutableProperty()->setName('courier');
        $obj_prop_filter1->mutableValue()->setStringValue('DHL-1');

        $obj_prop_filter2 = $obj_comp_filter->addFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter2->mutableProperty()->setName('enabled');
        $obj_prop_filter2->mutableValue()->setBooleanValue(true);

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_schema = new \GDS\Schema('Service');
        $obj_schema->addString('courier');

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store($obj_schema, $obj_gateway);
        $obj_store->fetchAll("SELECT * FROM Service WHERE courier = @courier AND enabled = @enabled", [
            'courier' => 'DHL-1',
            'enabled' => true,
        ]);

        $obj_deny_proxy->verify();
    }

    public function testSingleQuotedParamFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_query = $obj_request->mutableQuery();
        $obj_query->addKind()->setName('Book');
        $obj_prop_filter = $obj_query->mutableFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter->mutableProperty()->setName('author');
        $obj_prop_filter->mutableValue()->setStringValue('William Shakespeare');

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll("SELECT * FROM Book WHERE author = 'William Shakespeare'");

        $obj_deny_proxy->verify();
    }

    public function testDoubleQuotedParamFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_query = $obj_request->mutableQuery();
        $obj_query->addKind()->setName('Book');
        $obj_prop_filter = $obj_query->mutableFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter->mutableProperty()->setName('author');
        $obj_prop_filter->mutableValue()->setStringValue('William Shakespeare');

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll('SELECT * FROM Book WHERE author = "William Shakespeare"');

        $obj_deny_proxy->verify();
    }

    public function testBacktickQuotedParamFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_query = $obj_request->mutableQuery();
        $obj_query->addKind()->setName('Book');
        $obj_prop_filter = $obj_query->mutableFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter->mutableProperty()->setName('author');
        $obj_prop_filter->mutableValue()->setStringValue('William Shakespeare');

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll('SELECT * FROM Book WHERE author = `William Shakespeare`');

        $obj_deny_proxy->verify();
    }

    public function testMixedQuotedParamFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_query = $obj_request->mutableQuery();
        $obj_query->addKind()->setName('Book and stuff');
        $obj_comp_filter = $obj_query->mutableFilter()->mutableCompositeFilter()->setOperator(\google\appengine\datastore\v4\CompositeFilter\Operator::AND_);

        $obj_prop_filter1 = $obj_comp_filter->addFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter1->mutableProperty()->setName('author');
        $obj_prop_filter1->mutableValue()->setStringValue('William Shakespeare');

        $obj_prop_filter2 = $obj_comp_filter->addFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter2->mutableProperty()->setName('isbn');
        $obj_prop_filter2->mutableValue()->setStringValue('123456789');

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll('SELECT * FROM `Book and stuff` WHERE author = \'William Shakespeare\' AND isbn = "123456789"');

        $obj_deny_proxy->verify();
    }

    public function testMixedOverlappingQuotedParamFallback()
    {
        $obj_deny_proxy = new DenyGQLProxyMock();
        $obj_deny_proxy->init($this);

        $obj_request = new \google\appengine\datastore\v4\RunQueryRequest();
        $obj_request->setSuggestedBatchSize(1000);
        $obj_request->mutableReadOptions();
        $obj_partition = $obj_request->mutablePartitionId();
        $obj_partition->setDatasetId('Dataset');
        $obj_query = $obj_request->mutableQuery();
        $obj_query->addKind()->setName('Book and "stuff"');
        $obj_comp_filter = $obj_query->mutableFilter()->mutableCompositeFilter()->setOperator(\google\appengine\datastore\v4\CompositeFilter\Operator::AND_);

        $obj_prop_filter1 = $obj_comp_filter->addFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter1->mutableProperty()->setName('author');
        $obj_prop_filter1->mutableValue()->setStringValue('William "Will" Shakespeare');

        $obj_prop_filter2 = $obj_comp_filter->addFilter()->mutablePropertyFilter()->setOperator(\google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL);
        $obj_prop_filter2->mutableProperty()->setName('isbn');
        $obj_prop_filter2->mutableValue()->setStringValue("1234'5'6789");

        $obj_deny_proxy->expectCall('datastore_v4', 'RunQuery', $obj_request, new \google\appengine\datastore\v4\RunQueryResponse());

        $obj_gateway = new GDS\Gateway\ProtoBuf('Dataset');
        $obj_store = new GDS\Store('Book', $obj_gateway);
        $obj_store->fetchAll('SELECT * FROM `Book and "stuff"` WHERE author = \'William "Will" Shakespeare\' AND isbn = "1234\'5\'6789"');

        $obj_deny_proxy->verify();
    }

    /**
     * Test that when second condition property names start with "IN" we don't barf.
     */
    public function testMultiWhereWithInPrefix()
    {
        $obj_parser = new \GDS\Mapper\ProtoBufGQLParser();
        $obj_parser->parse('SELECT * FROM Person WHERE Test = "Thing" AND InstructionSet = "abc"');
        $this->assertEquals('Person', $obj_parser->getKind());
        $this->assertEquals([[
            'lhs' => 'Test',
            'op' => google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL,
            'comp' => '=',
            'rhs' => 'Thing'
        ],[
            'lhs' => 'InstructionSet',
            'op' => google\appengine\datastore\v4\PropertyFilter\Operator::EQUAL,
            'comp' => '=',
            'rhs' => 'abc'
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