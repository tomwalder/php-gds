<?php
/**
 * Query parameter examples
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// A single named parameter, "isbn"
$obj_book = $obj_book_store->fetchOne("SELECT * FROM Book WHERE isbn = @isbn", ['isbn' => '1840224339']);
describeResult($obj_book);

// Fetch n results
$arr_books = $obj_book_store->fetchAll("SELECT * FROM Book WHERE isbn < @isbn", ['isbn' => '1840224339']);
describeResult($arr_books);

// Query & pagination
$arr_books = $obj_book_store->query("SELECT * FROM Book WHERE isbn > @isbn", ['isbn' => '1840224339']);
while($arr_page = $obj_book_store->fetchPage(5)) {
    describeResult($arr_page);
}




/**
 * Helper function to simplify results display
 *
 * @param $mix_result
 * @param bool $bol_recurse
 */
function describeResult($mix_result, $bol_recurse = FALSE)
{
    if($mix_result instanceof GDS\Entity) {
        $str_class = get_class($mix_result);
        echo "Found single result: [{$str_class}] {$mix_result->getKeyId()}, {$mix_result->title}, {$mix_result->isbn}, {$mix_result->author}", PHP_EOL;
    } elseif (is_array($mix_result)) {
        echo "Found ", count($mix_result), " results", PHP_EOL;
        if($bol_recurse) {
            foreach($mix_result as $mix_row) {
                describeResult($mix_row);
            }
        }
    } else {
        echo "No result(s) found", PHP_EOL;
    }
}