<?php
/**
 * Fetch a single record from GDS and update it
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('../vendor/autoload.php');
require_once('config/setup.php');
require_once('Book.php');
require_once('BookRepository.php');

// We'll need a Google_Client, use our convenience method
$obj_client = GDS\Gateway::createGoogleClient(GDS_APP_NAME, GDS_SERVICE_ACCOUNT_NAME, GDS_KEY_FILE_PATH);

// Gateway requires a Google_Client and Dataset ID
$obj_gateway = new GDS\Gateway($obj_client, GDS_DATASET_ID);

// Repository requires a Gateway
$obj_book_repo = new BookRepository($obj_gateway);

// Retrieve
$obj_book = $obj_book_repo->fetchById('5066549580791808');
print_r($obj_book);

// Update & save
$obj_book->author = 'Tom ' . date('Y-m-d H:i:s');
print_r($obj_book_repo->put($obj_book));