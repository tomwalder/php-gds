<?php

$obj_store = new \GDS\Store('Book');
$arr_books = $obj_store->fetchAll();

?>

<div class="container">
    <div class="row">
        <h2>Book List</h2>
        <div class="col">
            <table class="table">
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Author</th>
                    <th scope="col">ISBN</th>
                </tr>
                <?php if (empty($arr_books)) { ?>
                    <tr><th scope="row">No books found</th></tr>
                <?php } else { ?>
                    <?php foreach ($arr_books as $obj_book) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($obj_book->title); ?></td>
                            <td><?php echo htmlspecialchars($obj_book->author); ?></td>
                            <td><?php echo htmlspecialchars($obj_book->isbn); ?></td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </table>
        </div>
        <div class="col">
            Raw results array - <code>print_r($arr_books);</code>
            <pre class="mt-2 bg-light"><?php print_r($arr_books); ?></pre>
        </div>
    </div>
</div>