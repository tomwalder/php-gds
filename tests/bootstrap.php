<?php
/**
 * Any set-up needed to run the tests
 *
 * @author Tom Walder <tom@docnet.nu>
 */

// Time zone
date_default_timezone_set('UTC');

// Autoloader
require_once(dirname(__FILE__) . '/../vendor/autoload.php');

// Base Test Files
require_once(dirname(__FILE__) . '/base/GDSTest.php');
require_once(dirname(__FILE__) . '/base/RESTv1Test.php');
require_once(dirname(__FILE__) . '/base/Simple.php');
require_once(dirname(__FILE__) . '/base/Book.php');
require_once(dirname(__FILE__) . '/base/DenyGQLProxyMock.php');
require_once(dirname(__FILE__) . '/base/FakeGuzzleClient.php');