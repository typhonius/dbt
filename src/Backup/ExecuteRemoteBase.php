<?php

namespace DBT\Backup;

use DBT\Exception\DatabaseDriverNotSupportedException;
use DBT\Structures\Environment;
use DBT\Structures\Site;
use phpseclib3\Net\SSH2;

abstract class ExecuteRemoteBase
{
    public SSH2 $ssh;
    public Site $site;
    public Environment $environment;

    public function __construct(SSH2 $ssh, Site $site, Environment $environment)
    {
        $this->ssh = $ssh;
        $this->site = $site;
        $this->environment = $environment;
    }

    public function downloadDatabase()
    {
        $credentials = $this->getDbCredentials();
        $password = escapeshellarg($credentials['password']);
        $dumpCommand = escapeshellcmd("mysqldump '-h{$credentials['host']}' '-P{$credentials['port']}' '-u{$credentials['username']}' '-p{$password}' '{$credentials['database']}'");

        return $this->ssh->exec($dumpCommand);
    }
}
