<?php
/**
 * Create a single record in GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// So now create a simple Model object
$obj_book = new Book();
$obj_book->title = 'Romeo and Juliet';
$obj_book->author = 'William Shakespeare';
$obj_book->isbn = '1840224339';

// Insert 1 into the Datastore
$bol_result = $obj_book_store->upsert($obj_book);
var_dump($bol_result);

// So now create a simple Model object (2)
$obj_book2 = new Book();
$obj_book2->title = "A Midsummer Night's Dream";
$obj_book2->author = 'William Shakespeare';
$obj_book2->isbn = '1853260304';

// So now create a simple Model object (3)
$obj_book3 = new Book();
$obj_book3->title = 'Hamlet';
$obj_book3->author = 'William Shakespeare';
$obj_book3->isbn = '1853260096';

$bol_multi_result = $obj_book_store->upsert([$obj_book2, $obj_book3]);
var_dump($bol_multi_result);

// Create using our factory method
$obj_book4 = $obj_book_store->createFromArray([
    'title' => 'The Merchant of Venice',
    'author' => 'William Shakespeare',
    'isbn' => '1840224312'
]);
$bol_factory_result = $obj_book_store->upsert($obj_book4);
var_dump($bol_factory_result);

