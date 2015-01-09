<?php
/**
 * Delete a single record from GDS (within a transaction)
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// Start transaction
$obj_book_store->beginTransaction();

// Fetch one record
$obj_book = $obj_book_store->fetchOne("SELECT * FROM Book");
echo "Title: {$obj_book->title}, ISBN: {$obj_book->isbn}, Author: {$obj_book->author}", PHP_EOL;

// Running the first update on a Store after brginTransaction() will consume the transaction
echo "Running upsert...", PHP_EOL;
$obj_book->author = 'Tommy ' . date('Y-m-d H:i:s');
$obj_book_store->upsert($obj_book);

// This second upsert will NOT be transactional
echo "Running another upsert...", PHP_EOL;
$obj_book->author = 'Tom Walder';
$obj_book_store->upsert($obj_book);



