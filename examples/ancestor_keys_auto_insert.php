<?php
/**
 * Create a hierarchy of records in GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

$obj_person_schema = (new GDS\Schema('Person'))->addString('name')->addString('description');

$obj_store = new GDS\Store($obj_person_schema, $obj_gateway);

// Create the parent
$obj_john = $obj_store->createEntity();
$obj_john->name = 'John Smiths';
$obj_john->description = 'A parent';
$obj_store->upsert($obj_john);


// Create a child
$obj_jane = $obj_store->createEntity();
$obj_jane->name = 'Jane Smiths';
$obj_jane->description = 'A child';
$obj_jane->setAncestry($obj_john);
$obj_store->upsert($obj_jane);

// Create a grand child
$obj_jo = $obj_store->createEntity();
$obj_jo->name = 'Jo Smiths';
$obj_jo->description = 'A child';
$obj_jo->setAncestry($obj_jane);
$obj_store->upsert($obj_jo);

// Now fetch and display the Entity Group
print_r($obj_store->fetchEntityGroup($obj_john));