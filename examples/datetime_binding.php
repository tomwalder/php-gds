<?php
/**
 * Query parameter examples - datetime bindings
 *
 * @author Tom Walder <tom@docnet.nu>
 */
require_once('boilerplate.php');

// Schema with datetime
$obj_task_schema = (new GDS\Schema('Task'))
    ->addString('title')
    ->addDatetime('due', TRUE);

// Store requires a Gateway and Schema
$obj_task_store = new GDS\Store($obj_task_schema, $obj_gateway);

// Insert some data, with datetime binding
$obj_task_1 = $obj_task_store->createEntity([
    'title' => 'My first task',
    'due' => new DateTime('+1 day')
]);
$obj_task_store->upsert($obj_task_1);

// Insert some data, with "normal" string format
$obj_task_2 = $obj_task_store->createEntity([
    'title' => 'My first task',
    'due' => date('Y-m-d H:10:00')
]);
$obj_task_store->upsert($obj_task_2);

// Fetch with datetime binding
$arr_results = $obj_task_store->fetchAll("SELECT * FROM Task WHERE due < @dtm", [
    'dtm' => new DateTime('+6 hours')
]);

describeResult($arr_results, TRUE);


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
        echo "Found single result: [{$str_class}] {$mix_result->getKeyId()}, {$mix_result->title}, {$mix_result->due}", PHP_EOL;
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