<?php

namespace BackupOop\Utils;

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

  private $keypass;

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

  /**
   * @return mixed
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * @return array
   */
  public function getBackup() {
    return $this->backup;
  }

  /**
   * @return mixed
   */
  public function getDocroot() {
    return $this->docroot;
  }

  /**
   * @return mixed
   */
  public function getEnvironment() {
    return $this->environment;
  }

  /**
   * @return mixed
   */
  public function getHostname() {
    return $this->hostname;
  }

  /**
   * @return mixed
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * @return mixed
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * @return mixed
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * @return mixed
   */
  public function getServer() {
    return $this->server;
  }

  /**
   * @return mixed
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @param mixed $keypass
   */
  public function setKeypass($keypass) {
    $this->keypass = $keypass;
  }

  public function isKeypassEntered() {
    return isset($this->keypass);
  }

  /**
   * @param string $bootstrap
   * @param string $command
   * @return string
   * @throws Ssh2ConnectionException
   */
  public function execRemoteCommand($bootstrap = 'DRUPAL_BOOTSTRAP_FULL', $command = '') {
    $remote_command = "php -r '\$_SERVER[\"SCRIPT_NAME\"] = \"/\"; \$_SERVER[\"HTTP_HOST\"] = \"{$this->url}\"; define(\"DRUPAL_ROOT\", \"{$this->path}\"); require_once DRUPAL_ROOT . \"/includes/bootstrap.inc\"; drupal_bootstrap({$bootstrap}); {$command};'";
    $connection = ssh2_connect($this->hostname, $this->port);

    if (!ssh2_auth_pubkey_file($connection, $this->user, $this->key . '.pub', $this->key, $this->keypass)) {
      throw new Ssh2ConnectionException(sprintf("Could not connect to %s on port %d as %s", $this->hostname, $this->port, $this->user));
    }

    $stream = ssh2_exec($connection, $remote_command);
    stream_set_blocking($stream, true);
    $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    return stream_get_contents($stream_out);
  }

}
