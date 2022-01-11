<?php

namespace DBT\Structures;

use phpseclib3\Crypt\Common\AsymmetricKey;
use phpseclib3\Crypt\PublicKeyLoader;

class Server
{
    public $name;
    public $machine;
    public $hostname;
    public $user;
    public $port;
    public $password;
    public $key;
    public ?string $hostKey = null;

    public function __construct($server)
    {
        $this->name = $server['name'];
        $this->machine = $server['machine'];
        $this->hostname = $server['hostname'];
        $this->user = $server['user'];
        $this->port = $server['port'];
        $this->key = $server['key'] ?: null;
        $this->hostKey = $server['hostkey'] ?: null;
    }

    public function getHostname() : string
    {
        return $this->hostname;
    }


    public function getPort() : string
    {
        return $this->port;
    }

    public function getUser() : string
    {
        return $this->user;
    }

    public function setPassword($password) : Server
    {
        $this->password = $password;

        return $this;
    }

    public function getKey($password = null) : AsymmetricKey|string
    {
        if ($this->key) {
            if ($password) {
                return PublicKeyLoader::load(file_get_contents($this->key), $password);
            }
            return PublicKeyLoader::load(file_get_contents($this->key));
        }
        return $password;
    }

    public function getPassword() : ?string
    {
        return $this->password;
    }

    public function getHostKey() : ?string
    {
        return $this->hostKey;
    }
}
