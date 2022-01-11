<?php

namespace DBT\Backup;

use DBT\Config\ConfigLoader;
use DBT\Exception\InvalidComponentException;
use DBT\Exception\BackupException;
use DBT\Exception\Ssh2ConnectionException;
use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Backup
{
    public array $environments = [];
    public array $sites = ['*'];
    public array $servers = ['*'];
    public array $backup = ['code', 'db', 'files'];
    public string $destination = 'backups';
    public bool $unique = false;
    protected $password;

    protected $allowedServers = [];

    protected ConfigLoader $configLoader;
    protected OutputInterface $output;
    protected Filesystem $filesystem;

    public function __construct(ConfigLoader $configLoader, Filesystem $filesystem, OutputInterface $output)
    {
        $this->configLoader = $configLoader;
        $this->output = $output;
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
            throw new BackupException('Server not in available list');
        }

        $server = $this->allowedServers[$environment->server];
        $ssh = new SSH2($server->getHostname(), $server->getPort());
        $sftp = new SFTP($server->getHostname(), $server->getPort());
        $sftp->setTimeout(0);
        $sftp->setKeepAlive(10);

        if ($expected = $server->getHostkey()) {
            if ($expected != $ssh->getServerPublicHostKey()) {
                throw new Ssh2ConnectionException('Host key verification failed');
            }
        }

        $localFileOps = new LocalFileOperations($this->filesystem, $sftp, $environment, $this);

        $localFileOps->prepareBackupLocation();

        if (!$ssh->login($server->getUser(), $server->getKey($this->password))) {
            throw new Ssh2ConnectionException('Login failed');
        }
        $class = sprintf('\DBT\Backup\ExecuteRemote%d', $site->getVersion());
        $remote = new $class($ssh, $site, $environment);

        foreach ($this->backup as $backup) {
            $this->output->writeln(sprintf("<comment>Downloading %s from %s</comment>", $backup, $environment->id));
            if ($backup === 'db') {
                $database = $remote->downloadDatabase();
                file_put_contents($localFileOps->generateBackupLocation() . '/db/' . $environment->id . '.sql', $database);
            } elseif ($backup === 'files') {
                if (!$sftp->login($server->getUser(), $server->getKey($this->password))) {
                    throw new Ssh2ConnectionException('Login failed');
                }

                $listing = $sftp->rawlist($environment->path . '/' . $remote->loadPublicFilesPath(), true);
                var_dump($listing);
                die;
                $localFileOps->recursiveDownload($listing);
                if ($private = $remote->loadPrivateFilesPath()) {
                    $listing = $sftp->rawlist($environment->path . $private, true);
                    $localFileOps->recursiveDownload($listing);
                }
            }
            $this->output->writeln(sprintf("<info>%s downloaded to %s/%s</info>", $backup, $localFileOps->generateBackupLocation(), $backup));
        }

        $ssh->disconnect();
        $sftp->disconnect();
    }
}
