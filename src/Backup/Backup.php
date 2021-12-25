<?php

namespace DBT\Backup;

use DBT\Config\ConfigLoader;
use DBT\Exception\InvalidComponentException;
use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Backup
{
    public array $environments = [];
    public array $sites = ['*'];
    public array $servers = ['*'];
    public array $backup = ['code', 'db', 'files'];
    public string $destination = 'backups';
    protected $password;

    protected $allowedServers = [];

    protected ConfigLoader $configLoader;
    protected InputInterface $input;
    protected Filesystem $filesystem;

    public function __construct(ConfigLoader $configLoader, Filesystem $filesystem, InputInterface $input)
    {
        $this->configLoader = $configLoader;
        $this->input = $input;
        $this->filesystem = $filesystem;
    }

    public function setEnvironment(array $environments)
    {
        $this->environments = $environments;

        return $this;
    }

    public function setSite($sites)
    {
        $this->sites = $sites;

        return $this;
    }

    public function setServer($servers)
    {
        $this->servers = $servers;

        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function setBackup(array $backup)
    {
        if (count($backup) !== count(array_intersect(['code', 'db', 'files'], $backup))) {
            throw new InvalidComponentException(sprintf("The backup component(s) '%s' is invalid. Only code, files and db are accepted.", implode(',', $backup)));
        }
        $this->backup = $backup;

        return $this;
    }

    public function setBackupDestination($destination)
    {
        $this->destination = rtrim($destination, '/') . '/';

        return $this;
    }

    public function getBackupDestination()
    {
        return $this->destination;
    }

    public function setUnique($unique)
    {
        $this->unique = $unique;

        return $this;
    }

    public function getUnique()
    {
        return $this->unique;
    }

    public function backup()
    {
        $sites = $this->configLoader->loadSites($this->sites);

        $this->allowedServers = $this->configLoader->loadServers($this->servers);

        foreach ($sites as $site) {
            foreach ($site->environments as $environmentKey => $environment) {
                if ($this->environments && !in_array($environmentKey, $this->environments)) {
                    continue;
                }

                $this->download($site, $environment);
            }
        }
    }

    protected function download($site, $environment)
    {
        if (!array_key_exists($environment->server, $this->allowedServers)) {
            echo 'server not in available list';
            die;
        }

        $server = $this->allowedServers[$environment->server];
        $ssh = new SSH2($server->getHostname(), $server->getPort());
        $sftp = new SFTP($server->getHostname(), $server->getPort());

        if ($expected = $server->getHostkey()) {
            if ($expected != $ssh->getServerPublicHostKey()) {
                throw new \Exception('Host key verification failed');
            }
        }

        $localFileOps = new LocalFileOperations($this->filesystem, $sftp, $environment, $this);

        $localFileOps->prepareBackupLocation();

        foreach ($this->backup as $backup) {
            $debug = sprintf("Downloading %s from %s", $backup, $environment->id);
            var_dump($debug);

            if ($backup === 'db') {
                if (!$ssh->login($server->getUser(), $server->getKey($this->password))) {
                    throw new \Exception('Login failed');
                }

                $remote = new ExecuteRemote($ssh, $site, $environment);
                $database = $remote->downloadDatabase();

                file_put_contents($localFileOps->generateBackupLocation() . '/db/' . $environment->id . '.sql', $database);
            } elseif ($backup === 'files') {
                $sftp->login($server->getUser(), $server->getKey($this->password));

                $listing = $sftp->rawlist($environment->path . '/sites/default/files', true);

                $localFileOps->recursiveDownload($listing);
            }
        }

        $ssh->disconnect();
        $sftp->disconnect();
    }
}
