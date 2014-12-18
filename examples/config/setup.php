<?php
/**
 * Setup example
 *
 * @author Tom Walder <tom@docnet.nu>
 */
$str_path = dirname(__FILE__);
if(!file_exists($str_path . '/key.p12')) {
    throw new Exception('No P12 key file - see README');
}
if(!file_exists($str_path . '/config.php')) {
    throw new Exception('No config.php file - see README');
}
require_once('config.php');
date_default_timezone_set('Europe/London');