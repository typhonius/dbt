<?php

namespace BackupOop\Config;

use BackupOop\Utils\DrupalSite;

interface ConfigInterface {

  public function getSites();

  public function getBackupLocation(DrupalSite $site, $component);

  public function serverDefaults();

  public function runPostBackupTasks();
}