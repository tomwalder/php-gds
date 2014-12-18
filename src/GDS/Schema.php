<?php
/**
 * GDS Model Schema
 *
 * @author Tom Walder <tom@docnet.nu>
 */
namespace GDS;

class Schema
{

    /**
     * Field data types
     */
    const FIELD_STRING = 1;
    const FIELD_INTEGER = 2;
    const FIELD_DATETIME = 3;
    const FIELD_DOUBLE = 4;
    const FIELD_FLOAT = 4; // FLOAT === DOUBLE
    const FIELD_BOOLEAN = 10; // 10 types of people...
    const FIELD_STRING_LIST = 20;

    /**
     * Kind (like database 'Table')
     *
     * @var string|null
     */
    private $str_kind = NULL;

    /**
     * Known fields
     *
     * @var array
     */
    private $arr_fields = [];

    /**
     * Kind is required
     *
     * @param $str_kind
     */
    public function __construct($str_kind)
    {
        $this->str_kind = $str_kind;
    }

    /**
     * Add a field to the known field array
     *
     * @param $str_name
     * @param $int_type
     * @param bool $bol_index
     * @return $this
     */
    public function addField($str_name, $int_type = self::FIELD_STRING, $bol_index = FALSE)
    {
        $this->arr_fields[$str_name] = [
            'type' => $int_type,
            'index' => $bol_index
        ];
        return $this;
    }

    /**
     * Get the Kind
     *
     * @return string
     */
    public function getKind()
    {
        return $this->str_kind;
    }

    /**
     * Get the configured fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->arr_fields;
    }

}