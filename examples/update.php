<?php
/**
 * Delete a single record from GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// Fetch one record
$obj_book = $obj_book_store->fetchOne("SELECT * FROM Book");
echo "   Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;

// Update the author
$obj_book->author = 'Tom ' . date('Y-m-d H:i:s');
$obj_book_store->upsert($obj_book);
