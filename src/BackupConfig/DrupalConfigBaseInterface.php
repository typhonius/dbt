<?php

namespace DrupalBackup\BackupConfig;

use DrupalBackup\DrupalSite;

/**
 * DrupalConfigBaseInterface is the interface implemented by all backup classes.
 *
 * Interface DrupalConfigBaseInterface
 * @package DrupalBackup\BackupConfig
 */
interface DrupalConfigBaseInterface
{

    /**
     * Prepares the path location to where the backup should be stored.
     *
     * @param DrupalSite $site
     * @param string     $component
     */
    public function prepareBackupLocation(DrupalSite $site, $component);

    /**
     * Returns the command that needs to be executed for a backup to run.
     *
     * @param DrupalSite $site
     * @param string     $component
     * @return array
     */
    public function getBackupCommand(DrupalSite $site, $component);

    /**
     * Returns the docroots loaded from configuration that will be backed up.
     *
     * @param array $servers
     * @param array $sites
     * @param array $envs
     * @return mixed
     */
    public function getDocroots(array $servers, array $sites, array $envs);

    /**
     * @return array
     */
    public function loadSites();

    /**
     * @return array
     */
    public function loadServers();

    /**
     * @param string $location
     * @param array  $selected
     * @return array
     */
    public function loadConfig($location, $selected);
}
