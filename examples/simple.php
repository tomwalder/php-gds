<?php
/**
 * Simplest GDS example - no schema, Kind only
 *
 * @author Tom Walder <twalder@gmail.com>
 */
require_once('../vendor/autoload.php');

// Store requires a Gateway and Kind
$obj_book_store = new GDS\Store('Book');

// Fetch a record
$obj_book = $obj_book_store->fetchOne();

// Dump the result
print_r($obj_book);