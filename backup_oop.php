#!/usr/bin/php
<?php

require_once "includes/bootstrap.inc";

try {
  $application->run();
}
catch (Exception $e) {
  $e->stderr(NULL, TRUE);
}

