<?php
/**
 * Attempt to increment the number of times a book has been read
 *
 * Contains a 5 second sleep so you can run concurrently for testing
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// Re-try once
try {
    increment_reads($obj_book_store);
} catch (\Google_Service_Exception $obj_ex) {
    if(409 == $obj_ex->getCode()) {
        echo "Contention, trying again...", PHP_EOL;
        increment_reads($obj_book_store);
    } else {
        echo "FAILED with [", $obj_ex->getCode(), "] ", $obj_ex->getMessage(), PHP_EOL;
    }
}

/**
 * Increment within a transaction
 *
 * @param $obj_book_store
 */
function increment_reads($obj_book_store) {

    // Load a root node, so we have an Entity Group to work with
    $obj_book = $obj_book_store->fetchOne("SELECT * FROM Book");

    // Start transaction
    $obj_book_store->beginTransaction();

    // Fetch one record (using entity group)
    $arr_books = $obj_book_store->fetchEntityGroup($obj_book);
    $obj_txn_book = $arr_books[0];
    echo "Title: {$obj_txn_book->title}, ISBN: {$obj_txn_book->isbn}, Author: {$obj_txn_book->author}, Reads: {$obj_txn_book->reads}", PHP_EOL;

    sleep(5);

    // Running the first update on a Store after beginTransaction() will consume the transaction
    echo "Running upsert...", PHP_EOL;
    $obj_book->reads = $obj_book->reads + 1;
    $obj_book_store->upsert($obj_book);
    echo "Complete OK", PHP_EOL;
}
