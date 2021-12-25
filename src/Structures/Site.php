<?php

namespace DBT\Structures;

class Site
{
    public $id;
    public $name;
    public $version;
    public $environments = [];

    public function __construct($site)
    {
        $this->name = $site['name'];
        $this->machine = $site['machine'];
        $this->version = $site['version'];
        $this->parseEnvironments($site['environments']);
    }

    private function parseEnvironments($environments)
    {
        foreach ($environments as $key => $environment) {
            $this->environments[$key] = new Environment($this->machine, $key, $environment);
        }
    }

    public function getEnvironments()
    {
        return $this->environments;
    }

    public function getVersion()
    {
        return $this->version;
    }
}
