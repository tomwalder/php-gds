<?php
/**
 * Name-spaced FETCH examples for GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('../_includes.php');

// Define our Book Schema
$obj_schema = (new GDS\Schema('Book'))
    ->addString('title', FALSE)
    ->addString('author')
    ->addString('isbn')
    ->addDatetime('published', FALSE)
    ->addInteger('pages', FALSE);

// This Store uses the default Protocol Buffer Gateway - for App Engine local development or live App Engine
// BUT, this time With a namespace defined ("ns1")
$obj_gateway_ns1 = new GDS\Gateway\ProtoBuf(null, 'ns1');
$obj_store_ns1 = new \GDS\Store($obj_schema, $obj_gateway_ns1);

// This Store uses the default Protocol Buffer Gateway - for App Engine local development or live App Engine
// BUT, this time With a namespace defined ("ns2")
$obj_gateway_ns2 = new GDS\Gateway\ProtoBuf(null, 'ns2');
$obj_store_ns2 = new \GDS\Store($obj_schema, $obj_gateway_ns2);

// Fetch all (client 1)
echo "From ns1", PHP_EOL;
$arr_books1 = $obj_store_ns1->fetchAll("SELECT * FROM Book");
echo "Query client 1 found ", count($arr_books1), " records", PHP_EOL;
foreach($arr_books1 as $obj_book) {
    echo "   Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
}

// Fetch all (client 2)
echo "From ns2", PHP_EOL;
$arr_books2 = $obj_store_ns2->fetchAll("SELECT * FROM Book");
echo "Query client 2 found ", count($arr_books2), " records", PHP_EOL;
foreach($arr_books2 as $obj_book) {
    echo "   Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
}
