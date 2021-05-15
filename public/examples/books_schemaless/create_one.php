<?php

$obj_store = new \GDS\Store('Book');

// Create a simple Entity object
$obj_book = new GDS\Entity();
$obj_book->title = 'Romeo and Juliet';
$obj_book->author = 'William Shakespeare';
$obj_book->isbn = '1840224339';

// Insert into the Datastore
$obj_store->upsert($obj_book);

?>

<div class="container">
    <div class="row">
        <h2>Create Book</h2>
        <div class="col">
            Created Book with ID <code><?php echo $obj_book->getKeyId(); ?></code>
        </div>
    </div>
</div>