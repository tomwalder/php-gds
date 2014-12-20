<?php
/**
 * @author Tom Walder <tom@docnet.nu>
 */
namespace GDS;

/**
 * Google Datastore Repository
 *
 * @package GDS
 */
abstract class Store
{

    /**
     * @var Gateway
     */
    private $obj_gateway = NULL;

    /**
     * Gateway required on construction
     *
     * @param Gateway $obj_gateway
     */
    public function __construct(Gateway $obj_gateway)
    {
        $this->obj_gateway = $obj_gateway;
    }

    /**
     * Write one or more changed Model objects to the Datastore
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
        $arr_results = $this->obj_gateway->gql($str_query);
        return $this->mapFromResults($arr_results);
    }

    /**
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
     * Map results from the Gateway into Model objects
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
     * Create a new instance of the GDS Entity class
     *
     * @return Model
     */
    abstract public function createModel();

}