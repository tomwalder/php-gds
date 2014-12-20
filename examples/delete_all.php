<?php
/**
 * Delete all records from GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// Fetch and delete
$arr_books = $obj_book_store->query("SELECT * FROM Book");
echo "Found ", count($arr_books), " records", PHP_EOL;
$obj_book_store->delete($arr_books);



