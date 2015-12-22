<?php
/**
 * Read data based on Ancestor Keys
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

$obj_person_schema = (new GDS\Schema('Person'))->addString('name')->addString('description');

$obj_store = new GDS\Store($obj_person_schema, $obj_gateway);

// Load the parent (run 'ancestor_keys.php' if needed to create it)
$obj_stored_parent = $obj_store->fetchOne("SELECT * FROM Person WHERE __key__ = KEY(Person, 'parent@example.com')");

// All "Person" entities in the group (INCLUDING the root)
$arr = $obj_store->fetchAll("SELECT * FROM Person WHERE __key__ HAS ANCESTOR @person", ['person' => $obj_stored_parent]);
print_r($arr);

// Now just load one (which is a nested entity)
print_r($obj_store->fetchAll("SELECT * FROM Person WHERE __key__ = @person", ['person' => $arr[1]]));
