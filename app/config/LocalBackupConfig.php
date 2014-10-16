<?php

namespace BackupOop\Config;

use BackupOop\Utils\File;
use BackupOop\Utils\DrupalSite;


class LocalBackupConfig extends ConfigBase {

  public function getBackupLocation(DrupalSite $site, $component) {
    global $configs;

    $path = $site->getDocroot() . '/' . $site->getenvironment() . '/' . $site->getServer();

    switch ($component) {
      case 'code':
        $path .= '/' . CODEDIR;
        break;
      case 'files':
        $path .= '/' . FILEDIR;
        break;
      case 'db':
        $path .= '/' . DBDIR;
        break;
    }

    if (isset($configs['backup'])) {
      File::checkDirectory($configs['backup'] . '/' . $path);
      return $configs['backup'] . '/' . $path;
    }
    else {
      File::checkDirectory(ROOT_DIR . '/backups/' . $path);
      return ROOT_DIR . '/backups/' . $path;
    }
  }

}
