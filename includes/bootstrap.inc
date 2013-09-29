<?php

define('ROOT_DIR', dirname(dirname(__FILE__)));
define('CONFIG', ROOT_DIR . '/config');

require_once ROOT_DIR . '/includes/common.inc';

// Load class autoloader
$loader = require_once ROOT_DIR . '/vendor/autoload.php';
$loader->add('backup', ROOT_DIR . '/app');
$loader->add('utils', ROOT_DIR . '/app');

