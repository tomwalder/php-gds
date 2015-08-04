<?php
/**
 * Move data between name spaces
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

// Fetch a record from the first namespace
$obj_book = $obj_store_ns1->fetchOne();

// =========
// IMPORTANT
// =========
// The book inserted to ns2 here will have the SAME keyId or keyName
// BUT will be in a different namespace, so will still be uniquely addressable
// As the "fully qualified" key of an Entity in GDS includes the namespace

// Insert into the second namesapce
$obj_store_ns2->upsert($obj_book);

