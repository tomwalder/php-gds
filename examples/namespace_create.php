<?php
/**
 * Namespace CREATE examples for GDS
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('namespace_boilerplate.php');

// Create a Book in the first namespace
$obj_book1 = $obj_book_store_client1->createFromArray([
    'title' => 'Romeo and Juliet',
    'author' => 'William Shakespeare',
    'isbn' => '1840224339'
]);
$bol_result1 = $obj_book_store_client1->upsert($obj_book1);
var_dump($bol_result1);

// Create a Book in the second namespace
$obj_book2 = $obj_book_store_client2->createFromArray([
    'title' => 'Hamlet',
    'author' => 'William Shakespeare',
    'isbn' => '1853260096'
]);
$bol_result2 = $obj_book_store_client2->upsert($obj_book2);
var_dump($bol_result2);

