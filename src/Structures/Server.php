<?php

namespace DBT\Structures;

use phpseclib3\Crypt\PublicKeyLoader;

class Server
{
    public $name;
    public $machine;
    public $hostname;
    public $user;
    public $port;
    public $key;
    public $password;
    public $hostkey;

    public function __construct($server)
    {
        $this->name = $server['name'];
        $this->machine = $server['machine'];
        $this->hostname = $server['hostname'];
        $this->user = $server['user'];
        $this->port = $server['port'];
        if (isset($server['key'])) {
            $this->key = $server['key'];
        }
        if (isset($server['hostkey'])) {
            $this->hostKey = $server['hostkey'];
        }
    }

    public function getHostname()
    {
        return $this->hostname;
    }


    public function getPort()
    {
        return $this->port;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getKey($password = null)
    {
        if ($this->key) {
            if ($password) {
                return PublicKeyLoader::load(file_get_contents($this->key), $password);
            }
            return PublicKeyLoader::load(file_get_contents($this->key));
        }
        return $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getHostKey()
    {
        return $this->hostKey;
    }
}
