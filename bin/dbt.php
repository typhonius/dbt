#!/usr/bin/env php
<?php

use DBT\DBT;

require_once dirname(__DIR__).'/vendor/autoload.php';

try {
    $dbt = new DBT();
    $dbt->run();
} catch (Exception $e) {
    throw $e;
}
