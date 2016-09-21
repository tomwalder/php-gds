<?php
/**
 * GDS Example - Create one record, with no Schema
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('../_includes.php');

// This Store uses the default Protocol Buffer Gateway - for App Engine local development or live App Engine
$obj_store = new \GDS\Store('Book');

// Alternative Gateway (remote JSON API)
// Download your service JSON file from the Google Developer Console
// $obj_gateway = new \GDS\Gateway\RESTv1('your-app-id');
// $obj_store = new \GDS\Store('Book', $obj_gateway);

// Create a simple Entity object
$obj_book = new GDS\Entity();
$obj_book->title = 'Romeo and Juliet';
$obj_book->author = 'William Shakespeare';
$obj_book->isbn = '1840224339';

// Insert into the Datastore
$obj_store->upsert($obj_book);

// Show the key
echo "Created: ", $obj_book->getKeyId(), PHP_EOL;