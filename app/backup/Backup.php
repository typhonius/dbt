<?php
/**
 * Created by JetBrains PhpStorm.
 * User: adam.malone
 * Date: 20/9/13
 * Time: 12:02 59
 * To change this template use File | Settings | File Templates.
 */

namespace backup;

class Backup {
  public $docroot;
  public $env;
  public $backup_path;

  public function runBackup($options) {
    // use rsync
    // dir structure
    // - server
    // -- docroot
    // --- env
    // ---- files
    // ---- code (server-docroot-env-date.tar.gz
    global $configs;

    $this->docroot = 'adammalone_net';
    $this->env = 'test';
    $this->generateBackupDirName();

    if (!file_exists($this->backup_path)) { // TODO include force parameter
      @mkdir($this->backup_path, 0755, TRUE);

    }
    //exec('rsync -aPh hermes:/var/www/html/adammalone/docroot/sites/all/modules/views /tmp');

  }

  private function generateBackupDirName() {
    global $configs;
    $dir = $this->getBackupServer() . "-{$this->docroot}-{$this->env}-" . date('Y-m-d');
    $this->backup_path = "{$configs->local}/{$dir}";
  }

  private function getBackupServer() {
    global $configs;
    return $configs->docroots[$this->docroot]->data['environments'][$this->env]['server'];
  }

}