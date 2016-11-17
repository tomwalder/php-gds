<?php
/**
 * Delete a single record from GDS
 *
 * @author Tom Walder <twalder@gmail.com>
 */
require_once('boilerplate.php');

// Fetch one record, delete it
$obj_book = $obj_book_store->fetchOne();
echo "Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
$obj_book_store->delete($obj_book);

