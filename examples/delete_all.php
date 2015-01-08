<?php
/**
 * Delete all records from GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// Fetch and delete ALL records
$arr_books = $obj_book_store->fetchAll();
echo "Found ", count($arr_books), " records", PHP_EOL;
$obj_book_store->delete($arr_books);



