<?php

namespace BackupOop\Config;

use BackupOop\Utils\File;
use Symfony\Component\Yaml\Yaml;
use BackupOop\Utils\DrupalSite;

abstract class ConfigBase implements ConfigInterface {

  protected $servers;
  protected $docroots;
  protected $environments;

  protected $sites;

  // TODO, this should have all general things to do with loading config
  // and backing up to where. The classes extending this should just alter
  // where things are stored.

  //TODO consider not loading servers here and only loading them when needed
  // TODO split loading servers / docroots into own functions called fomr __construct

  public function __construct($servers, $docroots, $environments) {
    $this->servers = $servers;
    $this->docroots = $docroots;
    $this->environments = $environments;
    $this->filterSites();
  }

  public function getSites() {
    return $this->sites;
  }

  public function filterSites() {
    $docroot_info = $this->getDocroots();
    $server_info = $this->getServers();

    foreach ($docroot_info as &$docroot) {
      foreach ($docroot['environments'] as $env_id => &$environment) {
        if (isset($server_info[$environment['server']]) && empty($this->environments) || in_array($env_id, $this->environments)) {
          $server = $server_info[$environment['server']];
          $environment['docroot'] = $docroot['machine'];
          $environment['machine'] = $env_id;
          $this->sites[] = new DrupalSite($server, $environment);
        }
      }
    }
  }

  protected function getDocroots() {
    $docroots = [];
    foreach (File::loadFiles(CONFIG . '/docroots', '/.*\.yml/') as $docroot_conf) {
      $docroot = Yaml::parse($docroot_conf->uri);
      if (empty($this->docroots) || in_array($docroot['machine'], $this->docroots)) {
        $docroots[$docroot['machine']] = $docroot;
      }
    }

    return $docroots;
  }

  protected function getServers() {
    $servers = [];
    foreach (File::loadFiles(CONFIG . '/servers', '/.*\.yml/') as $server_conf) {
      $server = Yaml::parse($server_conf->uri);
      if (empty($this->servers) || in_array($server['machine'], $this->servers)) {
        $servers[$server['machine']] = $server + $this->serverDefaults();
      }
    }

    return $servers;
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

  public function serverDefaults() {
    return array (
      'hostname' => 'localhost',
      'port'     => 22,
      'user'     => 'root',
      'path'     => '/var/www/html',
    );
  }

  public function getBackupLocation(DrupalSite $site, $component = NULL) {}

  public function runPostBackupTasks() {}

}
