<?php

namespace DrupalBackup\BackupConfig;

use DrupalBackup\DrupalSite;
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
    public function getDocroots(array $servers = array(), array $sites = array(), array $envs = array())
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
                //$docroot->setEnvironment($envName);
                //$docroot->setDocroot($site['machine']);
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
    public function loadSites($sites = array())
    {
        return $this->loadConfig(self::CONFIG_DIR.'/sites', $sites);
    }

    /**
     * @param array $servers
     * @return array
     */
    public function loadServers($servers = array())
    {
        return $this->loadConfig(self::CONFIG_DIR.'/servers', $servers);
    }

    /**
     * @param string $location
     * @param string $selected
     * @return array
     */
    public function loadConfig($location, $selected = array())
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
