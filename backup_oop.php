#!/usr/bin/php
<?php

require_once "includes/bootstrap.inc";
use Symfony\Component\Console\Application;
use  utils\BackupException;

use backup\BackupCommand;

try {
  globalise_opts($argv);

  $application = new Application();
  $application->add(new BackupCommand());
  $application->run();
}
catch (Exception $e) {
  $e->stderr(NULL, TRUE);
}

