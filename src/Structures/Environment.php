<?php

namespace DBT\Structures;

class Environment
{

    public $id;

    public $path;

    public $url;

    public $server;

    public function __construct($site, $key, $environment)
    {
        $this->id = sprintf("%s.%s", $site, $key);
        $this->path = $environment['path'];
        $this->url = $environment['url'];
        $this->server = $environment['server'];
    }
}
