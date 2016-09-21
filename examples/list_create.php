<?php
/**
 * Create or some records in GDS  * with an indexed string field *
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('../vendor/autoload.php');

// We'll need a Gateway (for REST API usage, NOT on App Engine)
$obj_gateway = new \GDS\Gateway\RESTv1('my-app-id-here');

// Define the model on-the-fly
$obj_contact_schema = (new GDS\Schema('Contact'))
    ->addString('first_name')
    ->addString('last_name')
    ->addStringList('tags', TRUE);

// Configure the Store
$obj_store = new GDS\Store($obj_contact_schema, $obj_gateway);

// Create 1
$obj_contact1 = $obj_store->createEntity([
    'first_name' => 'Tom',
    'last_name' => 'Walder',
    'tags' => ["customer", "newsletter"]
]);
$obj_contact1->setKeyName('tom@docnet.nu');

// Create 2
$obj_contact2 = $obj_store->createEntity([
    'first_name' => 'Thomas',
    'last_name' => 'Walder',
    'tags' => ["newsletter", "api"]
]);
$obj_contact2->setKeyName('beermonster@gmail.com');

// Upsert
$obj_store->upsert([$obj_contact1, $obj_contact2]);