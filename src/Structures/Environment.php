<?php

namespace DBT\Structures;

class Environment
{

    public $id;
    public $path;
    public $url;
    public $server;
    public $publicFilesPath = 'sites/default/files';
    public $privateFilesPath = 'sites/default/files/private';

    public function __construct($site, $key, $environment)
    {
        $this->id = sprintf("%s.%s", $site, $key);
        $this->path = $environment['path'];
        $this->url = $environment['url'];
        $this->server = $environment['server'];
    }

    public function setPublicFilesPath($path)
    {
        $this->publicFilesPath = $path;

        return $this;
    }

    public function setPrivateFilesPath($path)
    {
        $this->privateFilesPath = $path;

        return $this;
    }
}
