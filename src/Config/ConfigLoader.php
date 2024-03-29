<?php

namespace DBT\Config;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use DBT\Structures\Site;
use DBT\Structures\Server;

final class ConfigLoader
{

    public $sites = [];
    public $servers = [];

    public static function instantiate()
    {
        return new static();
    }

    public function loadAll() : ConfigLoader
    {
        $this->sites = self::loadSites();
        $this->servers = self::loadServers();

        return $this;
    }

    public function getSites() : array
    {
        return $this->sites;
    }

    public function getServers() : array
    {
        return $this->servers;
    }

    public function loadSites($name = ['*']) : array
    {
        $sites = [];

        foreach ($this->loadAndParse('sites', $name) as $site) {
            $s = new Site($site);
            $sites[$s->machine] = $s;
        }

        return $sites;
    }

    public function loadServers($name = ['*']) : array
    {
        $servers = [];

        foreach ($this->loadAndParse('servers', $name) as $server) {
            $s = new Server($server);
            $servers[$s->machine] = $s;
        }

        return $servers;
    }

    public function loadAndParse($configPath, $name = ['*']) : array
    {
        $config = [];

        $allowedFiles = preg_filter('/$/', '.yml', $name);

        $finder = new Finder();
        $finder->files()->name($allowedFiles)->in(DBT_CONFIG . $configPath);
        if ($finder->hasResults()) {
            // @TODO add an exeption here or something
        }

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();
            $config[] = Yaml::parseFile($absoluteFilePath);
        }

        return $config;
    }
}
