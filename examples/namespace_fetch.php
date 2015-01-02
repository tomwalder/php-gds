<?php
/**
 * Namespace FETCH examples for GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('namespace_boilerplate.php');

// Fetch all (client 1)
$arr_books1 = $obj_book_store_client1->fetchAll("SELECT * FROM Book");
echo "Query client 1 found ", count($arr_books1), " records", PHP_EOL;
foreach($arr_books1 as $obj_book) {
    echo "   Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
}

// Fetch all (client 2)
$arr_books2 = $obj_book_store_client2->fetchAll("SELECT * FROM Book");
echo "Query client 2 found ", count($arr_books2), " records", PHP_EOL;
foreach($arr_books2 as $obj_book) {
    echo "   Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
}