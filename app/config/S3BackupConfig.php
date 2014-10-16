<?php

namespace BackupOop\Config;

use Symfony\Component\Yaml\Yaml;


class S3Config extends RemoteConfig {
  public $local;
  public $servers;
  public $docroots;

  public function __construct() {
    self::loadConfig();
  }

  public function loadConfig() {
    $local = File::loadFiles(CONFIG, '/local.config.yml/');
    $this->local = Yaml::parse($local[0]->uri);
    $servers = File::loadFiles(CONFIG . '/servers', '/.*\.yml/');
    $docroots = File::loadFiles(CONFIG . '/docroots', '/.*\.yml/');

    // TODO ensure some are loaded else error
    // TODO Create server with port 22 as default

    foreach ($servers as &$server) {
      $server->data = Yaml::parse($server->uri);
      $this->servers[$server->data['machine']] = $server;
    }
    foreach ($docroots as &$docroot) {
      $docroot->data = Yaml::parse($docroot->uri);
      $this->docroots[$docroot->data['machine']] = $docroot;
    }
  }

  public function getConfigByKey($stage, $key) {
    // TODO return all server info perhaps? Or all docroot info - this would be useful for SCPing stuff
    // Might be better to use getServerConfig below to get the server config...
  }

  public function getServerConfig($server) {
    return $this->servers[$server]->data;
  }

  public function getDocrootConfig($docroot) {
    return $this->docroots[$docroot]->data;
  }

  public function returnInfoArray($stage, $param) {
    foreach ($this->$stage as $obj) {
      $return[] = $obj->data[$param];
    }
    return $return;

    // TODO add in methods for server and docroot loading
  }

  public function isValidConfig($docroot, $server, $env) {
    // First ensure the docroot machine name is in existence.
    if ($this->docroots[$docroot]) {
      // Now check that the environment exists for this docroot.
      if (array_key_exists($env, $this->docroots[$docroot]->data['environments'])) {
        // Finally ensure that the server passed to this function is in place on that environment.
        if ($server == $this->docroots[$docroot]->data['environments'][$env]['server']) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  public function getDocrootList() {
    foreach ($this->docroots as $docroot) {
      $docroots[] = $docroot->data['name'];
    }
    return $docroots;
  }

  public function getBackupLocation() {
    if (File::checkDirectory($this->local['backup'])) {
      return $this->local['backup'];
    }
    elseif (File::checkDirectory(ROOT_DIR . "/backups")) {
      return ROOT_DIR . "/backups";
    }
    else {
      return '/tmp';
    }
  }

}
