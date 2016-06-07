<?php

namespace DrupalBackup\BackupConfig;

use DrupalBackup\DrupalSite;

interface DrupalConfigBaseInterface
{
    public function getBackupClass();

    public function getBackupLocation(DrupalSite $site, $component);

    public function getBackupCommand(DrupalSite $site, $component);

    public function getDocroots($servers, $sites, $envs);

    public function getSites();
}
