<?php
/**
 * Create a single record in GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

$obj_store = new GDS\Store('Friend', $obj_gateway);

// So now create a simple Model object
$obj_charlie = new GDS\Entity();
$obj_charlie->name = 'Charlie';
$obj_charlie->age = 26;
$obj_charlie->height = 120;
$obj_store->upsert($obj_charlie);
echo "Created: ", $obj_charlie->getKeyId(), PHP_EOL;

// So now create a simple Model object
$obj_max = new GDS\Entity();
$obj_max->name = 'Max';
$obj_max->age = 26;
$obj_max->height = 122;
$obj_store->upsert($obj_max);
echo "Created: ", $obj_max->getKeyId(), PHP_EOL;

echo "Query 1:", PHP_EOL;
foreach($obj_store->fetchAll("SELECT * FROM Friend WHERE age = 26") as $obj_result) {
    echo "Got: ", $obj_result->getKeyId(), ' ' , $obj_result->name, PHP_EOL;
}

echo "Query 2:", PHP_EOL;
foreach($obj_store->fetchAll("SELECT * FROM Friend WHERE age = 26 AND height = 122") as $obj_result) {
    echo "Got: ", $obj_result->getKeyId(), ' ' , $obj_result->name, PHP_EOL;
}