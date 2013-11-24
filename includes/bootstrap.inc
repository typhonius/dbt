<?php

use Symfony\Component\Console\Application;
Use Symfony\Component\Yaml\Yaml;
use backup\BackupCommand;
use utils\ConfigFileNotFoundException;

define('CONFIG', ROOT_DIR . '/config');

global $configs;

try {
  if (!file_exists(CONFIG . '/local/local.config.yml')) {
    throw new ConfigFileNotFoundException('Local config file not found.');
  }
  $configs = Yaml::parse(CONFIG . '/local/local.config.yml');
}
catch (Exception $e) {
  exit($e->getMessage() . "\n");
}

// Load class Application and add our Classes
$application = new Application();
$application->setName('Drupal backup tool');
$application->setVersion('1.0');
$application->add(new BackupCommand());
