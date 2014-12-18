<?php
/**
 * GDS Model
 *
 * @author Tom Walder <tom@docnet.nu>
 */
namespace GDS;

class EntityMapper
{

    /**
     * @var Schema
     */
    private $obj_schema = NULL;

    public function __construct(Schema $obj_schema)
    {
        $this->obj_schema = $obj_schema;
    }

    /**
     * Create a Datastore Entity from a Model
     *
     * @param Model $obj_model
     * @return \Google_Service_Datastore_Entity
     */
    public function createFromModel(Model $obj_model)
    {
        $obj_entity = new \Google_Service_Datastore_Entity();
        $obj_entity->setKey($this->createKey($obj_model));
        $obj_entity->setProperties($this->createProperties($obj_model));
        return $obj_entity;
    }

    /**
     * Create an Entity Key from a Model
     *
     * @todo handle Ancestor keys
     *
     * @param Model $obj_model
     * @return \Google_Service_Datastore_Key
     */
    private function createKey(Model $obj_model)
    {
        $obj_path = new \Google_Service_Datastore_KeyPathElement();
        $obj_path->setKind($this->obj_schema->getKind());

        // Existing Key Data?
        if (NULL !== $obj_model->getKeyId()) {
            $obj_path->setId($obj_model->getKeyId());
        } else if (NULL !== $obj_model->getKeyName()) {
            $obj_path->setName($obj_model->getKeyName());
        }

        $obj_key = new \Google_Service_Datastore_Key();
        $obj_key->setPath([$obj_path]);
        return $obj_key;
    }

    /**
     * Build & return the properties for the Model
     *
     * @param Model $obj_model
     * @return array
     */
    private function createProperties(Model $obj_model)
    {
        $arr_property_map = [];
        foreach ($this->obj_schema->getFields() as $str_field => $arr_field_def) {
            if ($obj_model->hasData($str_field)) {
                $arr_property_map[$str_field] = $this->createProperty($arr_field_def, $obj_model->{$str_field});
            }
        }
        return $arr_property_map;
    }

    /**
     * Create a property object
     *
     * @param array $arr_field_def
     * @param $mix_value
     * @return mixed
     */
    private function createProperty(array $arr_field_def, $mix_value)
    {
        $obj_property = new \Google_Service_Datastore_Property();

        // Indexed?
        $bol_index = FALSE;
        if(isset($arr_field_def['index']) && TRUE === $arr_field_def['index']) {
            $bol_index = TRUE;
        }
        $obj_property->setIndexed($bol_index); // @todo validate this works OK for indexed STRING_LIST

        switch ($arr_field_def['type']) {
            case Schema::FIELD_STRING:
                $obj_property->setStringValue((string)$mix_value);
                break;

            case Schema::FIELD_INTEGER:
                $obj_property->setIntegerValue((int)$mix_value);
                break;

            case Schema::FIELD_DATETIME:
                $obj_property->setDateTimeValue((new \DateTime($mix_value))->format(\DateTime::ATOM));
                break;

            case Schema::FIELD_DOUBLE:
            case Schema::FIELD_FLOAT:
                $obj_property->setDoubleValue(floatval($mix_value));
                break;

            case Schema::FIELD_BOOLEAN:
                $obj_property->setBooleanValue((bool)$mix_value);
                break;

            case Schema::FIELD_STRING_LIST:
                $arr_values = [];
                foreach ((array)$mix_value as $str) {
                    $obj_value = new \Google_Service_Datastore_Value();
                    $obj_value->setStringValue($str);
                    $obj_value->setIndexed($bol_index);
                    $arr_values[] = $obj_value;
                }
                $obj_property->setListValue($arr_values);
                break;

            default:
                throw new \RuntimeException('Unable to process field type: ' . $arr_field_def['type']);

        }
        return $obj_property;
    }


    /**
     * Map a single result out of the Raw response data array into a supplied Model object
     *
     * @param $arr_result
     * @param Model $obj_model
     * @return Model
     * @throws \Exception
     */
    public function mapFromRawData($arr_result, Model $obj_model)
    {
        if(isset($arr_result['entity']['key']['path'])) {
            $arr_path = $arr_result['entity']['key']['path'];

            // KEY for 'self' (the last part of the KEY PATH)
            $arr_path_end = end($arr_path);
            if(isset($arr_path_end['id'])) {
                $obj_model->setKeyId($arr_path_end['id']);
            }
            if(isset($arr_path_end['name'])) {
                $obj_model->setKeyName($arr_path_end['name']);
            }

            // @todo DO WE HAVE A Parent KEY?
            // if(count($arr_path) > 1) {
            //     $arr_parent = [$arr_path[0]['kind'], $arr_path[0]['name']];
            //     $obj_model->setParent($arr_parent);
            // }
        } else {
            throw new \RuntimeException("No path for Entity Key?");
        }

        $arr_field_defs = $this->obj_schema->getFields();
        foreach ((array)$arr_result['entity']['properties'] as $str_field => $obj_property) {
            /** @var $obj_property \Google_Service_Datastore_Property */
            if (isset($arr_field_defs[$str_field])) {
                $obj_model->__set($str_field, $this->extractPropertyValue($arr_field_defs[$str_field]['type'], $obj_property));
            } else {
                throw new \RuntimeException('Undefined field data: ' . $str_field);
            }
        }
        return $obj_model;
    }

    /**
     * Extract a single property value from a Property object
     *
     * @param $int_type
     * @param \Google_Service_Datastore_Property $obj_property
     * @return array
     * @throws \Exception
     */
    private function extractPropertyValue($int_type, \Google_Service_Datastore_Property $obj_property)
    {
        switch ($int_type) {
            case Schema::FIELD_STRING:
                return $obj_property->getStringValue();

            case Schema::FIELD_INTEGER:
                return $obj_property->getIntegerValue();

            case Schema::FIELD_DATETIME:
                return $obj_property->getDateTimeValue();

            case Schema::FIELD_DOUBLE:
            case Schema::FIELD_FLOAT:
                return $obj_property->getDoubleValue();

            case Schema::FIELD_BOOLEAN:
                return $obj_property->getBooleanValue();

            case Schema::FIELD_STRING_LIST:
                $arr = [];
                foreach($obj_property->getListValue() as $obj_val) {
                    $arr[] = $obj_val->getStringValue();
                }
                return $arr;
        }
        throw new \Exception('Unsupported field type: ' . $int_type);
    }

}