<?php

namespace DrupalBackup\BackupConfig;

use DrupalBackup\DrupalSite;
use DrupalBackup\Exception\UnsupportedVersionException;
use DrupalBackup\File;

/**
 * Class AbstractDrupalConfigBase
 * @package DrupalBackup\BackupConfig
 */
abstract class AbstractDrupalConfigBase implements DrupalConfigBaseInterface
{
    const CONFIG_DIR = ROOT_DIR.'/app/config';

    /**
     * @param array $servers
     * @param array $sites
     * @param array $envs
     * @return array
     */
    public function getDocroots(array $servers = [], array $sites = [], array $envs = [])
    {
        $sites = $this->loadSites($sites);
        $servers = $this->loadServers($servers);

        $docroots = [];

        foreach ($sites as $site) {
            foreach ($site['environments'] as $envName => $environment) {
                // Filter out invalid environments.
                if (!empty($envs) && !in_array($envName, $envs)) {
                    continue;
                }
                // Filter out invalid servers.
                if (!empty($servers) && !array_key_exists($environment['server'], $servers)) {
                    continue;
                }

                $docrootId = $site['machine'].".".$envName;
                $docroot = new DrupalSite($docrootId);
                if (!in_array($site['version'], [6, 7, 8])) {
                    throw new UnsupportedVersionException(sprintf("Unsupported Drupal version '%d'.", $site['version']));
                }
                $docroot->setVersion($site['version']);
                $docroot->setPath($environment['path']);
                $docroot->setUrl($environment['url']);

                // Cast backup to array in case it is empty in the config file.
                $docroot->setBackupOptions((array) $environment['backup']);

                $docroot->setServer($servers[$environment['server']]);
                $docroots[$docrootId] = $docroot;
            }
        }

        return $docroots;
    }

    /**
     * @param array $sites
     * @return array
     */
    public function loadSites($sites = [])
    {
        return $this->loadConfig(self::CONFIG_DIR.'/sites', $sites);
    }

    /**
     * @param array $servers
     * @return array
     */
    public function loadServers($servers = [])
    {
        return $this->loadConfig(self::CONFIG_DIR.'/servers', $servers);
    }

    /**
     * @param string $location
     * @param string $selected
     * @return array
     */
    public function loadConfig($location, $selected = [])
    {
        $configs = [];

        foreach (File::loadFiles($location, '/.*\.yml/') as $conf) {
            $config = File::parse($conf);
            if (empty($selected) || in_array($config['machine'], $selected)) {
                $configs[$config['machine']] = $config;
            }
        }

        return $configs;
    }
}
