<?php

use Symfony\Component\Console\Application;
use backup\BackupCommand;
//use backup\ServerCommand;
//use backup\DocrootCommand;

define('ROOT_DIR', dirname(dirname(__FILE__)));
define('CONFIG', ROOT_DIR . '/config');

//require_once ROOT_DIR . '/includes/common.inc';

// Load class autoloader
$loader = require_once ROOT_DIR . '/vendor/autoload.php';
$loader->add('backup', ROOT_DIR . '/app');
$loader->add('utils', ROOT_DIR . '/app');

// Load class Application and add our Classes
$application = new Application();
$application->add(new BackupCommand());
//$application->add(new ServerCommand());
//$application->add(new DocrootCommand());

