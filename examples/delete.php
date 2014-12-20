<?php
/**
 * Delete a single record from GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// Fetch one record, delete it
$arr_books = $obj_book_store->query("SELECT * FROM Book LIMIT 1");
echo "Found ", count($arr_books), " records", PHP_EOL;
foreach($arr_books as $obj_book) {
    echo "   Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
    $obj_book_store->delete($obj_book);
}


