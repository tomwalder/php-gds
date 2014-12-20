<?php
/**
 * GDS Model
 *
 * @author Tom Walder <tom@docnet.nu>
 */
namespace GDS;

/**
 * Google Datastore Model
 *
 * @package GDS
 */
abstract class Model
{

    /**
     * GDS record Key ID
     *
     * @var string
     */
    private $str_key_id = NULL;

    /**
     * GDS record Key Name
     *
     * @var string
     */
    private $str_key_name = NULL;

    /**
     * Field Data
     *
     * @var array
     */
    private $arr_data = [];

    /**
     * Get the key ID
     *
     * @return string
     */
    public function getKeyId()
    {
        return $this->str_key_id;
    }

    /**
     * Get the key name
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->str_key_name;
    }

    /**
     * Set the key ID
     *
     * @param $str_key_id
     */
    public function setKeyId($str_key_id)
    {
        $this->str_key_id = $str_key_id;
    }

    /**
     * Set the key name
     *
     * @param $str_key_name
     */
    public function setKeyName($str_key_name)
    {
        $this->str_key_name = $str_key_name;
    }

    /**
     * Magic setter.. sorry
     *
     * @param $str_key
     * @param $mix_value
     */
    public function __set($str_key, $mix_value)
    {
        $this->arr_data[$str_key] = $mix_value;
    }

    /**
     * Magic getter.. sorry
     *
     * @param $str_key
     * @return null
     */
    public function __get($str_key)
    {
        if(isset($this->arr_data[$str_key])) {
            return $this->arr_data[$str_key];
        }
        return NULL;
    }

    /**
     * Does this Model instance contain data for the supplied field? Or any data if no field specified?
     *
     * @param $str_key
     * @return bool
     */
    public function hasData($str_key = NULL)
    {
        if(NULL === $str_key) {
            return !empty($this->arr_data);
        }
        return isset($this->arr_data[$str_key]);
    }

    /**
     * Get the entire data array
     *
     * @return array
     */
    public function getData()
    {
        return $this->arr_data;
    }



}