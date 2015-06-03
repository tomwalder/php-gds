<?php
/**
 * Create or some records in GDS  * with an indexed string field *
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('../vendor/autoload.php');
require_once('config/setup.php');

// We'll need a Google_Client, use our convenience method
$obj_google_client = GDS\Gateway\GoogleAPIClient::createGoogleClient(GDS_APP_NAME, GDS_SERVICE_ACCOUNT_NAME, GDS_KEY_FILE_PATH);
$obj_gateway = new GDS\Gateway\GoogleAPIClient($obj_google_client, GDS_DATASET_ID); // Optionally, namespace

// Define the model on-the-fly
$obj_contact_schema = (new GDS\Schema('Contact'))
    ->addString('first_name')
    ->addString('last_name')
    ->addStringList('tags', TRUE);

// Configure the Store
$obj_store = new GDS\Store($obj_gateway, $obj_contact_schema);

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