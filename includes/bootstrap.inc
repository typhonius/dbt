<?php

use Symfony\Component\Console\Application;
use backup\BackupCommand;
//use backup\ServerCommand;
//use backup\DocrootCommand;

define('ROOT_DIR', dirname(dirname(__FILE__)));
define('CONFIG', ROOT_DIR . '/config');

// TODO put this in some init function to load a local YAML file with config in.
// Perhaps in the config class tbh
global $configs;
$configs->local = 'backups';

//require_once ROOT_DIR . '/includes/common.inc';

// Load class autoloader
$loader = require_once ROOT_DIR . '/vendor/autoload.php';
$loader->add('backup', ROOT_DIR . '/app');
$loader->add('utils', ROOT_DIR . '/app');

// Load class Application and add our Classes
$application = new Application();
$application->setName('Drupal backup tool');
$application->setVersion('1.0');
$application->add(new BackupCommand());
