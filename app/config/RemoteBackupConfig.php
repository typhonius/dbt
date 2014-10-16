<?php

namespace BackupOop\Config;

use BackupOop\Utils\DrupalSite;

class RemoteConfig extends ConfigBase {

  public function getBackupLocation(DrupalSite $site, $component) {
    // @TODO alter this for a temporay path to be pushed somewhere remote later
    return '/tmp';
  }

}
