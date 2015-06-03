<?php
/**
 * Boilerplate for Namespace GDS examples
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('../vendor/autoload.php');
require_once('config/setup.php');

// Classes for our test
require_once('Book.php');
require_once('BookStore.php');

// We'll need a Google_Client, use our convenience method
$obj_client = GDS\Gateway\GoogleAPIClient::createGoogleClient(GDS_APP_NAME, GDS_SERVICE_ACCOUNT_NAME, GDS_KEY_FILE_PATH);

// Gateway & Store for the first namespace/client
$obj_gateway_client1 = new GDS\Gateway\GoogleAPIClient($obj_client, GDS_DATASET_ID, 'client1');
$obj_book_store_client1 = new BookStore($obj_gateway_client1);

// Gateway & Store for the second namespace/client
$obj_gateway_client2 = new GDS\Gateway\GoogleAPIClient($obj_client, GDS_DATASET_ID, 'client2');
$obj_book_store_client2 = new BookStore($obj_gateway_client2);