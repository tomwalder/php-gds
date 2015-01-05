<?php
/**
 * Fetch and upsert all records from GDS
 *
 * Intended for use when you have changed the index configuration for an Entity
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// Fetch paginated, using the default query
while($arr_page = $obj_book_store->fetchPage(50)) {
    echo "Page contains ", count($arr_page), " records, upsert-ing (re-indexing)", PHP_EOL;
    $obj_book_store->upsert($arr_page);
    echo "Upsert complete, attempting to fetch next page", PHP_EOL;
}
echo "No more data, complete.", PHP_EOL;