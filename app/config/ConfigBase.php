<?php

namespace config;

use utils\File;
use Symfony\Component\Yaml\Yaml;

abstract class ConfigBase {

  protected $servers;
  protected $docroots;
  protected $envs;

  // TODO, this should have all general things to do with loading config
  // and backing up to where. The classes extending this should just alter
  // where things are stored.

  //TODO consider not loading servers here and only loading them when needed
  // TODO split loading servers / docroots into own functions called fomr __construct

  public function __construct($servers, $docroots, $envs) {

    $docroot_files = File::loadFiles(CONFIG . '/docroots', '/.*\.yml/');

    foreach ($docroot_files as &$docroot) {
      $docroot->data = Yaml::parse($docroot->uri);
      if (empty($docroots) || in_array($docroot->data['machine'], $docroots)) {
        $this->docroots[$docroot->data['machine']] = $docroot;
        foreach ($docroot->data['environments'] as $environment => $env_data) {
          if (empty($envs) || in_array($environment, $envs)) {
            $this->envs["{$docroot->data['machine']}.{$environment}"] = $env_data;
          }
        }
      }
    }
  }

  /**
   * @param $variable
   * @return array
   */
  public function __get($variable) {
    if (isset($this->$variable)) {
      return $this->$variable;
    }
    return array();
  }

  public function getConfigByKey($stage, $key) {}

  public function getServerConfig($server) {
    if (isset($this->servers[$server])) {
      return $this->servers[$server];
    }
    else {
      $config = File::loadFiles(CONFIG . '/servers', '/' . $server . '\.yml/');
      // TODO sanity check to ensure server has been loaded otherwise exception
      $server_config = $config[0];
      $server_config->data = Yaml::parse($server_config->uri) + $this->serverDefaults();
      // Merge defaults in
      $this->servers[$server] = $server_config;
    }

    return $this->servers[$server];
  }

  public function serverDefaults() {
    return array (
      'hostname' => 'localhost',
      'port'     => 22,
      'user'     => 'root',
      'path'     => '/var/www/html',
    );
  }

  public function getDocrootConfig($docroot) {}

  public function returnInfoArray($stage, $param) {}

  public function isValidConfig($docroot, $server, $env) {}

  public function generateBackupLocation(DrupalSite $site) {}

  public function setBackupLocation() {}

  public function getBackupLocation() {}

}
