<?php

namespace DrupalBackup\BackupConfig;

use DrupalBackup\DrupalSite;

class RemoteConfig extends ConfigBase
{

    public function getBackupLocation(DrupalSite $site, $component)
    {
        // @TODO alter this for a temporay path to be pushed somewhere remote later
        return '/tmp';
    }
}
