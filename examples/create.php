<?php
/**
 * Create a single record in GDS
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

// So now create a simple Model object
$obj_book = new Book();
$obj_book->name = 'Romeo and Juliet';
$obj_book->author = 'William Shakespeare';
$obj_book->isbn = '1840224339';

// Insert into the Datastore
print_r($obj_book_repo->put($obj_book));