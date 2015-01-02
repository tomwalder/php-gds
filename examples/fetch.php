<?php
/**
 * Fetch all records from GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// Retrieve one by it's Datastore ID
$obj_book = $obj_book_store->fetchById('5066549580791808');
if($obj_book) {
    echo "Found, ISBN: ", $obj_book->isbn, PHP_EOL;
} else {
    echo "Single Book not found", PHP_EOL;
}

// Fetch all
$arr_books = $obj_book_store->fetchAll("SELECT * FROM Book");
echo "Query found ", count($arr_books), " records", PHP_EOL;
foreach($arr_books as $obj_book) {
    echo "   Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
}

// Fetch paginated
$obj_book_store->query('SELECT * FROM Book');
while($arr_page = $obj_book_store->fetchPage(5)) {
    echo PHP_EOL, "Page contains ", count($arr_page), " records", PHP_EOL;
    foreach ($arr_page as $obj_book) {
        echo "   Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
    }
}