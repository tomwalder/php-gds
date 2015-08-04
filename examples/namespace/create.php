<?php
/**
 * Name-spaced CREATE examples for GDS
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

// Create a Book in the first namespace
$obj_book1 = $obj_store_ns1->createEntity([
    'title' => 'Romeo and Juliet',
    'author' => 'William Shakespeare',
    'isbn' => '1840224339'
]);
$obj_store_ns1->upsert($obj_book1);

// Create a Book in the second namespace
$obj_book2 = $obj_store_ns2->createEntity([
    'title' => 'Hamlet',
    'author' => 'William Shakespeare',
    'isbn' => '1853260096'
]);
$obj_store_ns2->upsert($obj_book2);
