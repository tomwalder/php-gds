<?php
/**
 * Create a single record in GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

$obj_person_schema = (new GDS\Schema('Person'))->addString('name')->addString('description');

$obj_store = new GDS\Store($obj_person_schema, $obj_gateway);

// Create the parent
$obj_john = new \GDS\Entity();
$obj_john->name = 'John Smiths';
$obj_john->description = 'A parent';
$obj_john->setKeyName('parent@example.com');
$obj_store->upsert($obj_john);

$obj_stored_parent = $obj_store->fetchOne("SELECT * FROM Person WHERE __key__ = KEY(Person, 'parent@example.com')");

// Create a child
$obj_jane = new \GDS\Entity();
$obj_jane->name = 'Jane Smiths';
$obj_jane->description = 'A child';
$obj_jane->setKeyName('child@example.com');
$obj_jane->setAncestry($obj_stored_parent);
$obj_store->upsert($obj_jane);