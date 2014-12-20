<?php
/**
 * Boilerplate for GDS examples
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('../vendor/autoload.php');
require_once('config/setup.php');

// Classes for our test
require_once('Book.php');
require_once('BookStore.php');

// We'll need a Google_Client, use our convenience method
$obj_client = GDS\Gateway::createGoogleClient(GDS_APP_NAME, GDS_SERVICE_ACCOUNT_NAME, GDS_KEY_FILE_PATH);

// Gateway requires a Google_Client and Dataset ID
$obj_gateway = new GDS\Gateway($obj_client, GDS_DATASET_ID);

// Store requires a Gateway
$obj_book_store = new BookStore($obj_gateway);