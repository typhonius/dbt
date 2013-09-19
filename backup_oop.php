#!/usr/bin/php
<?php

require_once "includes/bootstrap.inc";

try {
  start_backup();
}
catch (Exception $e) {
  $e->stderr(NULL, TRUE);
}

