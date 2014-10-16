<?php

namespace BackupOop\Utils;

use BackupOop\Config\ConfigBase;
use BackupOop\Config\LocalBackupConfig;

class DrupalSite {

  /**
   * @var
   */
  protected $hostname;

  /**
   * @var
   */
  protected $port;

  /**
   * @var
   */
  protected $user;

  /**
   * @var
   */
  protected $key;

  /**
   * @var
   */
  protected $path;

  /**
   * @var
   */
  protected $url;

  /**
   * @var
   */
  protected $docroot;

  /**
   * @var
   */
  protected $environment;

  /**
   * @var
   */
  protected $server;

  /**
   * @var array
   */
  protected $backup = array();

  /**
   * @param $server
   * @param $environment
   */
  public function __construct($server, $environment) {
    $this->hostname = $server['hostname'];
    $this->port = $server['port'];
    $this->user = $server['user'];
    $this->key = $server['key'];
    $this->path = $environment['path'];
    $this->url = $environment['url'];
    $this->docroot = $environment['docroot'];
    $this->environment = $environment['machine'];
    $this->server = $server['machine'];
    $this->backup = isset($environment['backup']) ? $environment['backup'] : array('files', 'db', 'code');
  }

  public function getHostname() {
    return $this->hostname;
  }

  public function getPort() {
    return $this->port;
  }

  public function getUser() {
    return $this->user;
  }

  public function getKey() {
    return $this->key;
  }

  public function getPath() {
    return $this->path;
  }

  public function getUrl() {
    return $this->url;
  }

  public function getBackup() {
    return $this->backup;
  }

  public function getDocroot() {
    return $this->docroot;
  }

  public function getEnvironment() {
    return $this->environment;
  }

  public function getServer() {
    return $this->server;
  }

  /**
   * @param string $bootstrap
   * @param string $command
   * @return string
   */
  public function execRemoteCommand($bootstrap = 'DRUPAL_BOOTSTRAP_FULL', $command = '') {

    $remote_command = "php -r '\$_SERVER[\"SCRIPT_NAME\"] = \"/\"; \$_SERVER[\"HTTP_HOST\"] = \"{$this->url}\"; define(\"DRUPAL_ROOT\", \"{$this->path}\"); require_once DRUPAL_ROOT . \"/includes/bootstrap.inc\"; drupal_bootstrap({$bootstrap}); {$command};'";
    $connection = ssh2_connect($this->hostname, $this->port);
    if (!ssh2_auth_pubkey_file($connection, $this->user, $this->key . '.pub', $this->key, "")) {
      // Prompt for password
      // http://symfony.com/doc/current/components/console/helpers/questionhelper.html
    }
    $stream = ssh2_exec($connection, $remote_command);
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    return stream_get_contents($stream_out);
  }

}
