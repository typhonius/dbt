<?php

namespace backup;

interface ConfigBase {

  public function __construct();

  public function loadConfig();

  public function getConfigByKey($stage, $key);

  public function getServerConfig($server);

  public function getDocrootConfig($docroot);

  public function returnInfoArray($stage, $param);

  public function isValidConfig($docroot, $server, $env);

  public function getDocrootList();

  public function getBackupLocation();

}
