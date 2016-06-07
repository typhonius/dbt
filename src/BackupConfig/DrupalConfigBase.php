<?php

namespace DrupalBackup\BackupConfig;

use DrupalBackup\DrupalSite;
use DrupalBackup\File;

/**
 * Class DrupalConfigBase
 * @package DrupalBackup\BackupConfig
 */
class DrupalConfigBase implements DrupalConfigBaseInterface
{

    /**
     * @var array $localConfig
     */
    public $localConfig;

    /**
     * @var array $sites
     */
    public $sites;

    /**
     * @var array $servers
     */
    public $servers;

    /**
     * DrupalConfigBase constructor.
     * @param array $localConfig
     */
    public function __construct($localConfig)
    {
        $this->localConfig = $localConfig;
        // @TODO throw exception if there is no class or backup location?
    }

    /**
     * @return string
     */
    public function getBackupClass()
    {
        return $this->localConfig['class'];
    }

    /**
     * @param DrupalSite $site
     * @param string     $component
     * @return string
     */
    public function getBackupLocation(DrupalSite $site, $component)
    {
        return $this->localConfig['backup'];
    }

    /**
     * @param DrupalSite $site
     * @param string     $component
     */
    public function getBackupCommand(DrupalSite $site, $component)
    {
        // TODO: Implement getBackupCommand() method.
    }

    /**
     * @param array $servers
     * @param array $sites
     * @param array $envs
     * @return array
     */
    // @TODO change this to something other than getDocroots
    public function getDocroots($servers, $sites, $envs)
    {
        $docroots = [];

        $sites = $this->getSites($sites);
        $servers = $this->getServers($servers);

        foreach ($sites as $site) {
            foreach ($site['environments'] as $envName => $environment) {
                if (!in_array($envName, $envs)) {
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

    // @TODO change to getSites and setSites
    public function getSites($sites = array())
    {
        $this->sites = $this->loadConfig(ROOT_DIR.'/app/config/sites', $sites);

        return $this->sites;
    }

    // @TODO change to getServers and setServers
    protected function getServers($servers = array())
    {
        $this->servers = $this->loadConfig(ROOT_DIR.'/app/config/servers', $servers);

        return $this->servers;
    }

    /**
     * @param string $location
     * @param string $selected
     * @return array
     */
    private function loadConfig($location, $selected)
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
