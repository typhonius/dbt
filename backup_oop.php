#!/usr/bin/env php
<?php

define('ROOT_DIR', dirname(__FILE__));
require_once ROOT_DIR . "/includes/autoload.inc";
require_once ROOT_DIR . "/includes/bootstrap.inc";

try {
  $application->run();
}
catch (Exception $e) {
  throw $e;
}
