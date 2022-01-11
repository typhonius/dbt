<?php

namespace DBT\Structures;

class Site
{
    public $name;
    public $machine;
    public $version;
    public $environments = [];

    public function __construct($site)
    {
        $this->name = $site['name'];
        $this->machine = $site['machine'];
        $this->version = $site['version'];
        $this->parseEnvironments($site['environments']);
    }

    private function parseEnvironments($environments) : Site
    {
        foreach ($environments as $key => $environment) {
            $this->environments[$key] = new Environment($this->machine, $key, $environment);
        }

        return $this;
    }

    public function getEnvironments() : array
    {
        return $this->environments;
    }

    public function getVersion() : string
    {
        return $this->version;
    }
}
