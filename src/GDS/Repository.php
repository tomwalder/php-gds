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
abstract class Repository
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
     * Write a single Model object to the Datastore
     *
     * @param Model $obj_model
     * @return bool
     */
    public function put(Model $obj_model)
    {
        $obj_entity = (new Mapper($this->getSchema()))->createFromModel($obj_model);
        return $this->obj_gateway->put($obj_entity, $obj_model->isNew());
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
            $arr_models[] = $obj_mapper->mapFromRawData($arr_result, $this->createEntity());
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
    abstract public function createEntity();

}