<?php

namespace DrupalBackup\BackupConfig;

use DrupalBackup\DrupalSite;

/**
 * Class RemoteBackupConfig
 * @package DrupalBackup\BackupConfig
 */
class RemoteBackupConfig extends AbstractDrupalConfigBase
{

    /**
     * Prepares the path location to where the backup should be stored.
     *
     * @param DrupalSite $site
     * @param string     $component
     * @return string
     */
    public function prepareBackupLocation(DrupalSite $site, $component)
    {
        // TODO: Implement prepareBackupLocation() method.
    }

    /**
     * Returns the command that needs to be executed for a backup to run.
     *
     * @param DrupalSite $site
     * @param string     $component
     * @return string
     */
    public function getBackupCommand(DrupalSite $site, $component)
    {
        // TODO: Implement getBackupCommand() method.
    }
}
