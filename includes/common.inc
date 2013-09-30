<?php

use backup\Backup;
use backup\Config;
use Symfony\Component\Console\Input\ArgvInput;

function globalise_opts($argv){
  global $opts;

  $input = new ArgvInput();
  $opts['site'] = $input->getParameterOption(array('--site', '-s'));
}

function start_backup() {
  $config = new Config;
  $config->getAllConfig();

  $options = array(
    'environments' => array('all'),
    'docroots' => array('all'),
  );

  $backup = new Backup();
 // $backup->runBackup($config, $options);

}
