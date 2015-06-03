<?php
/**
 * Any set-up needed to run the tests
 */
require(dirname(__FILE__) . '/../vendor/autoload.php');

foreach($_SERVER['argv'] as $str_arg) {
    if(strpos($str_arg, '=')) {
        $arr_kv_pair = explode('=', $str_arg, 2);
        if('/google_appengine/php/sdk' == substr($arr_kv_pair[1], -25)) {
            require($arr_kv_pair[1] . '/google/appengine/runtime/autoloader.php');
        }
    }
}
