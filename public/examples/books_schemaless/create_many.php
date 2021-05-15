<?php

$obj_store = new \GDS\Store('Book');

// Create some Entity objects
$obj_romeo = $obj_store->createEntity([
    'title' => 'Romeo and Juliet',
    'author' => 'William Shakespeare',
    'isbn' => '1840224339'
]);
$obj_midsummer = $obj_store->createEntity([
    'title' => "A Midsummer Night's Dream",
    'author' => 'William Shakespeare',
    'isbn' => '1853260304'
]);

// Insert multiple into the Datastore
$arr_books = [$obj_romeo, $obj_midsummer];
$obj_store->upsert($arr_books);

?>

<div class="container">
    <div class="row">
        <h2>Create Book</h2>
        <div class="col">
            <?php foreach ($arr_books as $obj_book) { ?>
                Created Book with ID <code><?php echo $obj_book->getKeyId(); ?></code><br />
            <?php } ?>
        </div>
    </div>
</div>