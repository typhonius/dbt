<?php

use Symfony\Component\Console\Application;
use DrupalBackup\BackupCommand;
use DrupalBackup\Exception\Ssh2NotInstalledException;
use DrupalBackup\Exception\ConfigFileNotFoundException;
use Symfony\Component\Yaml\Yaml;

try {
    if (!extension_loaded('ssh2')) {
        throw new Ssh2NotInstalledException('The SSH2 PHP module is required for DrupalBackup.');
    }
} catch (Exception $e) {
    exit($e->getMessage() . "\n");
}

// Load app configuration
$local_config = ROOT_DIR . '/app/config/local/local.config.yml';
if (!file_exists($local_config)) {
    throw new ConfigFileNotFoundException('Local config file not found.');
}

$config = Yaml::parse(file_get_contents($local_config));
$class = $config['class'];
$config = new $class($config);

// Load class Application and add our Classes
$application = new Application();
$application->setName('Drupal Backup Tool');
$application->setVersion('1.2');
$application->add(new BackupCommand($config));
