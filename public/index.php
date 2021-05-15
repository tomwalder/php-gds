<?php
/**
 * PHP GDS Demo Front End
 *
 * Basic UI / App Engine Application for demonstrating core Datastore functionality
 */

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/_header.php';

    // Available demos
    $arr_examples = [
        'books_schemaless' => [
            'name' => 'Schemaless',
            'description' => 'Simple, schemaless entity ("Book") examples',
            'actions' => [
                'list' => 'List Books',
                'create_one' => 'Create one new Book',
                'create_many' => 'Create several Books',
                'delete_one' => 'Delete one Book',
                'delete_all' => 'Delete all Books',
            ]
        ],
        //        'books_with_schema' => [
        //            'name' => 'With a Schema',
        //            'description' => 'More entity examples, using a defined Schema ("Book")',
        //            'actions' => [],
        //        ],
    ];

    require_once __DIR__ . '/_nav.php';
    $bol_action = false;
    $str_demo = $_GET['demo'] ?? null;
    if (!empty($str_demo) && isset($arr_examples[$str_demo])) {
        $str_action = $_GET['action'] ?? null;
        if (!empty($str_action) && isset($arr_examples[$str_demo]['actions'][$str_action])) {
            $str_action_file = implode('/', [__DIR__, 'examples', $str_demo, $str_action]) . '.php';
            if (is_readable($str_action_file)) {
                $bol_action = true;
                require_once $str_action_file;
            }
        }
    }
    if (!$bol_action) {
        require_once __DIR__ . '/_home.php';
    }
    require_once __DIR__ . '/_footer.php';
} catch (\Throwable $obj_thrown) {
    echo "Something went wrong: " . $obj_thrown->getMessage();
}

