<?php

namespace DrupalBackup\BackupConfig;

use DrupalBackup\DrupalSite;

/**
 * Class RemoteConfig
 * @package DrupalBackup\BackupConfig
 */
class RemoteConfig extends ConfigBase
{

    /**
     * {@inheritdoc}
     */
    public function getBackupLocation(DrupalSite $site, $component)
    {
        // @TODO alter this for a temporay path to be pushed somewhere remote later
        return '/tmp';
    }
}
