<?php
/**
 * Any set-up needed to run the tests
 *
 * @author Tom Walder <tom@docnet.nu>
 */

// Time zone
date_default_timezone_set('UTC');

// Autoloader for GDS
require_once(dirname(__FILE__) . '/../vendor/autoload.php');

// Autoloader for the App Engine SDK
foreach($_SERVER['argv'] as $str_arg) {
    if(strpos($str_arg, '=')) {
        $arr_kv_pair = explode('=', $str_arg, 2);
        if('/google_appengine/php/sdk' == substr($arr_kv_pair[1], -25)) {
            require($arr_kv_pair[1] . '/google/appengine/runtime/autoloader.php');
        }
    }
}

// Base Test Files
require_once(dirname(__FILE__) . '/base/GDSTest.php');