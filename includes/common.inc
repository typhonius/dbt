<?php

use backup\Backup;
use backup\Config;

function start_backup() {
  $config = new Config;
  $config->getAllConfig();

  $options = array(
    'environments' => array('all'),
    'docroots' => array('all'),
  );

  $backup = new Backup();
  $backup->runBackup($config, $options);

}
