<?php

namespace DBT;

use Symfony\Component\Console\Application;
use DrupalBackup\BackupCommand;
use DrupalBackup\Exception\Ssh2NotInstalledException;
use DrupalBackup\Exception\ConfigFileNotFoundException;
use Symfony\Component\Yaml\Yaml;

define('ROOT_DIR', dirname(__DIR__));

/**
 * Class DBT
 * @package DBT
 */
class DBT
{
    /**
     * DBT constructor.
     */
    public function __construct()
    {
        try {
            if (!extension_loaded('ssh2')) {
                throw new Ssh2NotInstalledException('The SSH2 PHP module is required for DrupalBackup.');
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @throws ConfigFileNotFoundException
     * @throws Exception
     */
    public function run()
    {
        // Load app configuration
        $localConfig = ROOT_DIR.'/app/config/local/local.config.yml';
        if (!file_exists($localConfig)) {
            throw new ConfigFileNotFoundException(sprintf("Local config file not found at %s.", $localConfig));
        }

        $config = Yaml::parse(file_get_contents($localConfig));
        $class = $config['class'];
        $config = new $class($config);

        // Load class Application and add our Classes
        $application = new Application();
        $application->setName('Drupal Backup Tool');
        $application->setVersion('1.3');
        $application->add(new BackupCommand($config));
        $application->run();
    }
}
