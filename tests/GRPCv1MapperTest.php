<?php

use Google\Cloud\Datastore\V1\Entity as GRPC_Entity;
use Google\Cloud\Datastore\V1\PartitionId;

class GRPCv1MapperTest extends \PHPUnit\Framework\TestCase
{
    public function testValidValuesMapToGoogleEntity()
    {
        $obj_schema = (new \GDS\Schema('Person'))
            ->addString('name')
            ->addInteger('age')
            ->addFloat('weight')
            ->addGeopoint('location')
            ->addDatetime('dob');

        $obj_mapper = new \GDS\Mapper\GRPCv1();
        $obj_mapper
            ->setSchema($obj_schema);


        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setSchema($obj_schema);
        $obj_gds_entity->setKind('Person');

        $obj_gds_entity->name = 'Dave';
        $obj_gds_entity->age = 21;
        $obj_gds_entity->weight = 92.6;
        $obj_gds_entity->location = new \GDS\Property\Geopoint(1.2, 3.4);
        $obj_gds_entity->dob = new DateTime('1979-02-05 08:30:00');

        $obj_grpc_entity = new GRPC_Entity();

        $obj_mapper->mapToGoogle($obj_gds_entity, $obj_grpc_entity);


        $obj_properties = json_decode($obj_grpc_entity->serializeToJsonString())->properties;

        $this->assertTrue(property_exists($obj_properties->name, 'stringValue'));
        $this->assertTrue(property_exists($obj_properties->age, 'integerValue'));
        $this->assertTrue(property_exists($obj_properties->weight, 'doubleValue'));
        $this->assertTrue(property_exists($obj_properties->location, 'geoPointValue'));
        $this->assertTrue(property_exists($obj_properties->dob, 'timestampValue'));
    }

    public function testNullValuesMapToGoogleEntity()
    {
        $obj_schema = (new \GDS\Schema('Person'))
            ->addString('name')
            ->addInteger('age')
            ->addFloat('weight')
            ->addGeopoint('location')
            ->addDatetime('dob');

        $obj_mapper = new \GDS\Mapper\GRPCv1();
        $obj_mapper
            ->setSchema($obj_schema);


        $obj_gds_entity = new \GDS\Entity();
        $obj_gds_entity->setSchema($obj_schema);
        $obj_gds_entity->setKind('Person');

        $obj_gds_entity->name = null;
        $obj_gds_entity->age = null;
        $obj_gds_entity->weight = null;
        $obj_gds_entity->location = null;
        $obj_gds_entity->dob = null;

        $obj_grpc_entity = new GRPC_Entity();

        $obj_mapper->mapToGoogle($obj_gds_entity, $obj_grpc_entity);


        $obj_properties = json_decode($obj_grpc_entity->serializeToJsonString())->properties;

        $this->assertTrue(property_exists($obj_properties->name, 'nullValue'));
        $this->assertTrue(property_exists($obj_properties->age, 'nullValue'));
        $this->assertTrue(property_exists($obj_properties->weight, 'nullValue'));
        $this->assertTrue(property_exists($obj_properties->location, 'nullValue'));
        $this->assertTrue(property_exists($obj_properties->dob, 'nullValue'));
    }
}