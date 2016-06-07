#!/usr/bin/env php
<?php

define('ROOT_DIR', dirname(__DIR__));

require_once ROOT_DIR . '/vendor/autoload.php';
require_once ROOT_DIR . "/app/bootstrap.inc";

try {
    $application->run();
} catch (Exception $e) {
    throw $e;
}
