<?php
/**
 * Copyright 2014 Tom Walder
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
namespace GDS;

/**
 * GDS Datastore
 *
 * @author Tom Walder <tom@docnet.nu>
 * @package GDS
 * @abstract
 */
abstract class Store
{

    /**
     * The GDS Gateway we're going to use
     *
     * @var Gateway
     */
    private $obj_gateway = NULL;

    /**
     * The last GQL query
     *
     * @var string|null
     */
    private $str_last_query = NULL;

    /**
     * The last result cursor
     *
     * @var string|null
     */
    private $str_last_cursor = NULL;

    /**
     * Gateway required on construction
     *
     * @param Gateway $obj_gateway
     */
    public function __construct(Gateway $obj_gateway)
    {
        $this->obj_gateway = $obj_gateway;
        $this->str_last_query = 'SELECT * FROM ' . $this->getSchema()->getKind() . ' ORDER BY __key__ ASC';
    }

    /**
     * Write one or more new/changed Model objects to the Datastore
     *
     * @param mixed
     * @return bool
     */
    public function upsert($arr_models)
    {
        if($arr_models instanceof Model) {
            $arr_models = [$arr_models];
        }
        $arr_entities = (new Mapper($this->getSchema()))->createFromModels($arr_models);
        return $this->obj_gateway->putMulti($arr_entities);
    }

    /**
     * Delete one or more Model objects from the Datastore
     *
     * @param mixed
     * @return bool
     */
    public function delete($arr_models)
    {
        if($arr_models instanceof Model) {
            $arr_models = [$arr_models];
        }
        $arr_keys = (new Mapper($this->getSchema()))->createKeys($arr_models);
        return $this->obj_gateway->deleteMulti($arr_keys);
    }

    /**
     * Fetch a single Model from the Datastore, by it's Key ID
     *
     * @param $str_id
     * @return Model|null
     */
    public function fetchById($str_id)
    {
        return $this->mapOneFromResults(
            $this->obj_gateway->fetchById($this->getSchema()->getKind(), $str_id)
        );
    }

    /**
     * Fetch a single Model from the Datastore, by it's Key Name
     *
     * @param $str_name
     * @return Model|null
     */
    public function fetchByName($str_name)
    {
        return $this->mapOneFromResults(
            $this->obj_gateway->fetchByName($this->getSchema()->getKind(), $str_name)
        );
    }

    /**
     * Fetch Models based on a GQL query
     *
     * @param $str_query
     * @return Model[]
     */
    public function query($str_query)
    {
        $this->str_last_query = $str_query;
        $this->str_last_cursor = NULL;
        return $this;
    }

    /**
     * Fetch ONE Model based on a GQL query
     *
     * @param $str_query
     * @return Model
     */
    public function fetchOne($str_query = NULL)
    {
        if(NULL !== $str_query) {
            $this->query($str_query);
        }
        $arr_results = $this->obj_gateway->gql($this->str_last_query . ' LIMIT 1');
        return $this->mapOneFromResults($arr_results);
    }

    /**
     * Fetch Models based on a GQL query
     *
     * @param $str_query
     * @return Model[]
     */
    public function fetchAll($str_query = NULL)
    {
        if(NULL !== $str_query) {
            $this->query($str_query);
        }
        $arr_results = $this->obj_gateway->gql($this->str_last_query);
        return $this->mapFromResults($arr_results);
    }

    /**
     * Fetch (a page of) Models based on a GQL query
     *
     * @param $int_page_size
     * @param null $mix_offset
     * @return Model[]
     */
    public function fetchPage($int_page_size, $mix_offset = NULL)
    {
        $str_offset = '';
        $arr_params = [];
        if(NULL !== $mix_offset) {
            if(is_int($mix_offset)) {
                $str_offset = 'OFFSET @intOffset';
                $arr_params['intOffset'] = $mix_offset;
            } else {
                $str_offset = 'OFFSET @startCursor';
                $arr_params['startCursor'] = $mix_offset;
            }
        } else if (strlen($this->str_last_cursor) > 1) {
            $str_offset = 'OFFSET @startCursor';
            $arr_params['startCursor'] = $this->str_last_cursor;
        }
        $arr_results = $this->obj_gateway->gql($this->str_last_query . " LIMIT {$int_page_size} {$str_offset}", $arr_params);
        $this->str_last_cursor = $this->obj_gateway->getEndCursor();
        return $this->mapFromResults($arr_results);
    }

    /**
     * Get the last result cursor
     *
     * @return null|string
     */
    public function getCursor()
    {
        return $this->str_last_cursor;
    }

    /**
     * Set the query cursor
     *
     * Usually before continuing through a paged result set
     *
     * @param $str_cursor
     */
    public function setCursor($str_cursor)
    {
        $this->str_last_cursor = $str_cursor;
    }

    /**
     * Create a Model object and populate with data
     *
     * @param array $arr_data
     * @return Model
     */
    public function createFromArray(array $arr_data)
    {
        $obj_model = $this->createModel();
        foreach($arr_data as $str_property => $mix_value) {
            $obj_model->__set($str_property, $mix_value);
        }
        return $obj_model;
    }

    /**
     * Map a single query result into a Model object
     *
     * @param $arr_results
     * @return Model|null
     */
    private function mapOneFromResults($arr_results)
    {
        $arr_models = $this->mapFromResults($arr_results);
        if(count($arr_models) > 0) {
            return $arr_models[0];
        }
        return NULL;
    }

    /**
     * Map all query results from the Gateway into Model objects
     *
     * @param array $arr_results
     * @return Model[]
     */
    private function mapFromResults(array $arr_results)
    {
        $arr_models = [];
        $obj_mapper = new Mapper($this->getSchema());
        foreach ($arr_results as $arr_result) {
            $arr_models[] = $obj_mapper->mapFromRawData($arr_result, $this->createModel());
        }
        return $arr_models;
    }

    /**
     * Get the schema for this GDS Model
     *
     * @return Schema
     */
    abstract protected function getSchema();

    /**
     * Create a new instance of this GDS Model class
     *
     * @return Model
     */
    abstract public function createModel();

}