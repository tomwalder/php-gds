<?php

$obj_store = new \GDS\Store('Book');
$arr_books = $obj_store->fetchAll();

if (!empty($arr_books)) {
    $obj_store->delete($arr_books);
    $int_books = count($arr_books);
} else {
    $int_books = 0;
}

?>

<div class="container">
    <div class="row">
        <h2>Delete Books</h2>
        <div class="col">
            Deleted <?php echo $int_books; ?> books
        </div>
    </div>
</div>