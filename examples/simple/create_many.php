<?php
/**
 * GDS Example - Create several records in one upsert, with no Schema
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

// Create some Entity objects
$obj_romeo = $obj_store->createEntity([
    'title' => 'Romeo and Juliet',
    'author' => 'William Shakespeare',
    'isbn' => '1840224339'
]);
$obj_midsummer = $obj_store->createEntity([
    'title' => "A Midsummer Night's Dream",
    'author' => 'William Shakespeare',
    'isbn' => '1853260304'
]);

// Insert multiple into the Datastore
$arr_books = [$obj_romeo, $obj_midsummer];
$obj_store->upsert($arr_books);

// Show their keys
foreach ($arr_books as $obj_book) {
    echo "Created: ", $obj_book->getKeyId(), PHP_EOL;
}

