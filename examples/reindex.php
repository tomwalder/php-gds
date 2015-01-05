<?php
/**
 * Fetch and upsert all records from GDS
 *
 * Intended for use when you have changed the index configuration for an Entity
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// Fetch the first record (using the built-in "SELECT * FROM Kind" query)
$obj_first_book = $obj_book_store->fetchOne();
echo "Found one, ISBN: ", $obj_first_book->isbn, PHP_EOL, PHP_EOL;

// Retrieve one by it's Datastore ID
$str_id = '5066549580791808';
$obj_book = $obj_book_store->fetchById($str_id);
if($obj_book) {
    echo "Found, ISBN: ", $obj_book->isbn, PHP_EOL;
} else {
    echo "Single Book not found for ID: ", $str_id, PHP_EOL;
}
echo PHP_EOL;

// Fetch all
$arr_books = $obj_book_store->fetchAll("SELECT * FROM Book");
echo "Query found ", count($arr_books), " records", PHP_EOL;
foreach($arr_books as $obj_book) {
    echo "   Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
}
echo PHP_EOL;

// Fetch paginated
$obj_book_store->query('SELECT * FROM Book');
while($arr_page = $obj_book_store->fetchPage(5)) {
    echo PHP_EOL, "Page contains ", count($arr_page), " records", PHP_EOL;
    foreach ($arr_page as $obj_book) {
        echo "   Title: {$obj_book->title}, ISBN: {$obj_book->isbn}", PHP_EOL;
    }
}