<?php
/**
 * Simplest GDS example - no schema, Kind only
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('../vendor/autoload.php');
require_once('config/setup.php');

// We'll need a Google_Client, use our convenience method
$obj_client = GDS\Gateway\GoogleAPIClient::createGoogleClient(GDS_APP_NAME, GDS_SERVICE_ACCOUNT_NAME, GDS_KEY_FILE_PATH);

// Gateway requires a Google_Client and Dataset ID
$obj_gateway = new GDS\Gateway\GoogleAPIClient($obj_client, GDS_DATASET_ID);

// Store requires a Gateway and Kind
$obj_book_store = new GDS\Store($obj_gateway, 'Book');

// Fetch a record
$obj_book = $obj_book_store->fetchOne();

// Dump the result
print_r($obj_book);