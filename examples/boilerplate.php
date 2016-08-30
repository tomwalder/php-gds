<?php
/**
 * Boilerplate for GDS examples
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('../vendor/autoload.php');
require_once('config/setup.php');

require_once('Book.php');

// ============================================================================
// ============================================================================
// IMPORTANT - examples using this file operate on the remote Google API client
// ============================================================================
// ============================================================================

// Alternative native gateway, auto-detect dataset. Should work in dev or live AppEngine
// But not in scripts.
$obj_gateway = new GDS\Gateway\ProtoBuf();

// ============================================================================
// ============================================================================

// Define our Model Schema
$obj_book_schema = (new GDS\Schema('Book'))
    ->addString('title')
    ->addString('author')
    ->addString('isbn', TRUE)
    ->addDatetime('published', FALSE)
    ->addString('text', FALSE);

// Store requires a Gateway and Schema
$obj_book_store = new GDS\Store($obj_book_schema, $obj_gateway);
