<?php

// Load class autoloader
$loader = require_once ROOT_DIR . '/vendor/autoload.php';
$loader->add('config', ROOT_DIR . '/app');
$loader->add('backup', ROOT_DIR . '/app');
$loader->add('utils', ROOT_DIR . '/app');