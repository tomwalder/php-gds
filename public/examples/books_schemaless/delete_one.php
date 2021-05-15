<?php

$obj_store = new \GDS\Store('Book');
$obj_book = $obj_store->fetchOne();

if ($obj_book instanceof \GDS\Entity) {
    $obj_store->delete($obj_book);
} else {
    echo 'No books to delete';
    return;
}

?>

<div class="container">
    <div class="row">
        <h2>Delete Book</h2>
        <div class="col">
            Deleted Book with ID <code><?php echo $obj_book->getKeyId(); ?></code>
        </div>
    </div>
</div>