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
namespace GDS\Mapper;
use GDS\Exception\GQL;
use google\appengine\datastore\v4\PropertyFilter\Operator;
use google\appengine\datastore\v4\PropertyOrder\Direction;
use google\appengine\datastore\v4\Value;

/**
 * A *trivial* GQL Parser to hep with local App Engine development
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class ProtoBufGQLParser
{

    /**
     * We swap out quoted strings for simple tokens early on to help parsing simplicity
     */
    const TOKEN_PREFIX = '__token__';

    /**
     * Tokens detected
     *
     * @var array
     */
    private $arr_tokens = [];

    /**
     * A count of the tokens detected
     *
     * @var int
     */
    private $int_token_count = 0;

    /**
     * Kind for the query
     *
     * @var string
     */
    private $str_kind = null;

    /**
     * Any integer offset
     *
     * @var int
     */
    private $int_offset = null;

    /**
     * Any integer limit
     *
     * @var int
     */
    private $int_limit = null;

    /**
     * A string cursor (start, like offset)
     *
     * @var string
     */
    private $str_start_cursor = null;

    /**
     * A string cursor (end, like limit)
     *
     * @var string
     */
    private $str_end_cursor = null;

    /**
     * Conditions (filters)
     *
     * @var array
     */
    private $arr_conditions = [];

    /**
     * Order bys
     *
     * @var array
     */
    private $arr_order_bys = [];

    /**
     * Any provided named parameters
     *
     * @var array
     */
    private $arr_named_params = [];

    /**
     * Sort Direction options
     *
     * @var array
     */
    private $arr_directions = [
        'ASC' => Direction::ASCENDING,
        'DESC' => Direction::DESCENDING
    ];

    /**
     * Supported comparison operators
     *
     * Not supported by v4 Proto files?
     * 'IN', 'CONTAINS', 'HAS DESCENDANT'
     *
     * @var array
     */
    private $arr_operators = [
        '=' => Operator::EQUAL,
        '<' => Operator::LESS_THAN,
        '<=' => Operator::LESS_THAN_OR_EQUAL,
        '>' => Operator::GREATER_THAN,
        '>=' => Operator::GREATER_THAN_OR_EQUAL,
        'HAS ANCESTOR' => Operator::HAS_ANCESTOR
    ];

    /**
     * Turn a GQL string and parameter array into a "lookup" query
     *
     * We use preg_replace_callback to "prune" down the GQL string so we are left with nothing
     *
     * @param $str_gql
     * @param \google\appengine\datastore\v4\GqlQueryArg[] $arr_named_params
     * @throws GQL
     */
    public function parse($str_gql, $arr_named_params = [])
    {
        // Record our input params
        foreach($arr_named_params as $obj_param) {
            if($obj_param->hasValue()) {
                $this->arr_named_params[$obj_param->getName()] = $obj_param->getValue();
            } else if ($obj_param->hasCursor()) {
                $this->arr_named_params[$obj_param->getName()] = $obj_param->getCursor();
            }
        }

        // Cleanup before we begin...
        $str_gql = trim($str_gql);

        // Ensure it's a 'SELECT *' query
        if(!preg_match('/^SELECT\s+\*\s+FROM\s+(.*)/i', $str_gql)) {
            throw new GQL("Sorry, only 'SELECT *' (full Entity) queries are currently supported by php-gds");
        }

        // Tokenize quoted items ** MUST BE FIRST **
        $str_gql = preg_replace_callback("/([`\"'])(?<quoted>.*?)(\\1)/", [$this, 'tokenizeQuoted'], $str_gql);

        // Kind
        $str_gql = preg_replace_callback('/^SELECT\s+\*\s+FROM\s+(?<kind>[^\s]*)/i', [$this, 'recordKind'], $str_gql, 1);

        // Offset
        $str_gql = preg_replace_callback('/OFFSET\s+(?<offset>[^\s]*)/i', [$this, 'recordOffset'], $str_gql, 1);

        // Limit
        $str_gql = preg_replace_callback('/LIMIT\s+(?<limit>[^\s]*)/i', [$this, 'recordLimit'], $str_gql, 1);

        // Order
        $str_gql = preg_replace_callback('/ORDER\s+BY\s+(?<order>.*)/i', [$this, 'recordOrder'], $str_gql, 1);

        // Where
        $str_gql = preg_replace_callback('/WHERE\s+(?<where>.*)/i', [$this, 'recordWhere'], $str_gql, 1);

        // Check we're done
        $str_gql = trim($str_gql);
        if(strlen($str_gql) > 0) {
            throw new GQL("Failed to parse entire query, remainder: [{$str_gql}]");
        }
    }

    /**
     * Record quoted strings, return simple tokens
     *
     * @param $arr
     * @return string
     */
    private function tokenizeQuoted($arr)
    {
        $str_token = self::TOKEN_PREFIX . ++$this->int_token_count;
        $this->arr_tokens[$str_token] = $arr['quoted'];
        return $str_token;
    }

    /**
     * Record the Kind
     *
     * @param $arr
     * @return string
     */
    private function recordKind($arr)
    {
        $this->str_kind = $this->lookupToken($arr['kind']);
        return '';
    }

    /**
     * Record the offset
     *
     * @param $arr
     * @return string
     */
    private function recordOffset($arr)
    {
        list($this->int_offset, $this->str_start_cursor) = $this->getIntStringFromValue($this->lookupToken($arr['offset']));
        return '';
    }

    /**
     * Record the limit
     *
     * @param $arr
     * @return string
     */
    private function recordLimit($arr)
    {
        list($this->int_limit, $this->str_end_cursor) = $this->getIntStringFromValue($this->lookupToken($arr['limit']));
        return '';
    }

    /**
     * Extract a string/int tuple from the value. Used for offsets and limits which can be string cursors or integers
     *
     * @param $mix_val
     * @return array
     */
    private function getIntStringFromValue($mix_val)
    {
        $int = null;
        $str = null;
        if($mix_val instanceof Value) {
            if($mix_val->hasIntegerValue()) {
                $int = $mix_val->getIntegerValue();
            } else {
                $str = $mix_val->getStringValue();
            }
        } else {
            if(is_numeric($mix_val)) {
                $int = $mix_val;
            } else {
                $str = $mix_val;
            }
        }
        return [$int, $str];
    }

    /**
     * Process the ORDER BY clause
     *
     * @param $arr
     * @return string
     * @throws GQL
     */
    private function recordOrder($arr)
    {
        $arr_order_bys = explode(',', $arr['order']);
        foreach($arr_order_bys as $str_order_by) {
            $arr_matches = [];
            preg_match('/\s?(?<field>[^\s]*)\s*(?<dir>ASC|DESC)?/i', $str_order_by, $arr_matches);
            if(isset($arr_matches['field'])) {
                $str_direction = strtoupper(isset($arr_matches['dir']) ? $arr_matches['dir'] : 'ASC');
                if(isset($this->arr_directions[$str_direction])) {
                    $int_direction = $this->arr_directions[$str_direction];
                } else {
                    throw new GQL("Unsupported direction in ORDER BY: [{$arr_matches['dir']}] [{$str_order_by}]");
                }
                $this->arr_order_bys[] = [
                    'property' => $this->lookupToken($arr_matches['field']), // @todo @ lookup
                    'direction' => $int_direction
                ];
            }
        }
        return '';
    }

    /**
     * Process the WHERE clause
     *
     * @param $arr
     * @return string
     * @throws GQL
     */
    private function recordWhere($arr)
    {
        $arr_conditions = explode('AND', $arr['where']);
        $str_regex = '/(?<lhs>[^\s<>=]*)\s*(?<comp>=|<|<=|>|>=|IN|CONTAINS|HAS ANCESTOR|HAS DESCENDANT)\s*(?<rhs>[^\s<>=]+)/i';
        foreach($arr_conditions as $str_condition) {
            $arr_matches = [];
            if(preg_match($str_regex, trim($str_condition), $arr_matches)) {
                $str_comp = strtoupper($arr_matches['comp']);
                if(isset($this->arr_operators[$str_comp])) {
                    $int_operator = $this->arr_operators[$str_comp];
                } else {
                    throw new GQL("Unsupported operator in condition: [{$arr_matches['comp']}] [{$str_condition}]");
                }
                $this->arr_conditions[] = [
                    'lhs' => $arr_matches['lhs'],
                    'comp' => $str_comp,
                    'op' => $int_operator,
                    'rhs' => $this->lookupToken($arr_matches['rhs'])
                ];
            } else {
                throw new GQL("Failed to parse condition: [{$str_condition}]");
            }
        }
        return '';
    }

    /**
     * Lookup the field in our token & named parameter list
     *
     * Use array index string access for fast initial check
     *
     * @param $str_val
     * @return mixed
     */
    private function lookupToken($str_val)
    {
        if('__key__' === $str_val) {
            return $str_val;
        }
        if('_' === $str_val[0]) {
            if(isset($this->arr_tokens[$str_val])) {
                return $this->arr_tokens[$str_val];
            }
        }
        if('@' === $str_val[0]) {
            $str_bind_name = substr($str_val, 1);
            if(isset($this->arr_named_params[$str_bind_name])) {
                return $this->arr_named_params[$str_bind_name];
            }
        }
        return $str_val;
    }

    /**
     * Get the query Kind
     *
     * @return string
     */
    public function getKind()
    {
        return $this->str_kind;
    }

    /**
     * Get the query limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->int_limit;
    }

    /**
     * Get the offset
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->int_offset;
    }

    /**
     * Get any start cursor
     *
     * @return string
     */
    public function getStartCursor()
    {
        return $this->str_start_cursor;
    }

    /**
     * Get any end cursor
     *
     * @return string
     */
    public function getEndCursor()
    {
        return $this->str_end_cursor;
    }

    /**
     * Get any order bys
     *
     * @return array
     */
    public function getOrderBy()
    {
        return $this->arr_order_bys;
    }

    /**
     * Get any filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->arr_conditions;
    }


}