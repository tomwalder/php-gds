<?php
/**
 * Create, update and retrieve a series of Entities to test retrieval and mapping of Auto-insert IDs
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

$obj_store = new GDS\Store('Temperatures', $obj_gateway);

// Delete ALL
// $obj_store->delete($obj_store->fetchAll());

// Create some new records with random temperatures
$arr_new_records = [];
$int_new_records = mt_rand(10,20);
for($int = 1; $int <= $int_new_records; $int++) {
    $obj_new_entity = $obj_store->createEntity([
        'temp' => mt_rand(1,9999)
    ]);
    $arr_new_records[] = $obj_new_entity;
}
$obj_store->upsert($arr_new_records);

// Now keep a record of the mapping (POST upsert)
$arr_temp_id_map = [];
foreach($arr_new_records as $obj_temp) {
    $arr_temp_id_map[$obj_temp->getKeyId()] = $obj_temp->temp;
}

// Get all records from the Datastore and compare
$arr_all_temps = $obj_store->fetchAll();
foreach($arr_all_temps as $obj_stored_temp) {
    echo $obj_stored_temp->getKeyID() . " has temp " . $obj_stored_temp->temp;
    if(isset($arr_temp_id_map[$obj_stored_temp->getKeyID()])) {
        if($arr_temp_id_map[$obj_stored_temp->getKeyID()] == $obj_stored_temp->temp) {
            echo ", match OK";
        } else {
            echo ", Error: NO MATCH";
        }
    } else {
        echo ", which is not new this time round";
    }
    echo PHP_EOL;
}
