<?php

use backup\Backup;
use backup\Config;

function start_backup() {
  $config = new Config;
  $config->getConfig();
  $backup = new Backup;
}