<?php

namespace DBT\Backup\Remote;

use DBT\Exception\DatabaseDriverNotSupportedException;

class ExecuteRemote6 extends ExecuteRemoteBase
{

    public function send($command = '', $bootstrap = 'DRUPAL_BOOTSTRAP_FULL')
    {
        $remoteCommand = "cd \"{$this->environment->path}\"; php -r '\$_SERVER[\"REMOTE_ADDR\"] = \"127.0.0.1\"; \$_SERVER[\"SCRIPT_NAME\"] = \"/\"; \$_SERVER[\"HTTP_HOST\"] = \"{$this->environment->url}\"; define(\"DRUPAL_ROOT\", \"{$this->environment->path}\"); require_once DRUPAL_ROOT . \"/includes/bootstrap.inc\"; drupal_bootstrap({$bootstrap}); {$command};'";

        return $this->ssh->exec($remoteCommand);
    }

    protected function getDbCredentials()
    {

        $dbUrl = $this->send("global \$db_url; print serialize(\$db_url);", 'DRUPAL_BOOTSTRAP_CONFIGURATION');
        $dbComponents = parse_url($dbUrl);
        if (strpos($dbComponents['scheme'], 'mysql') !== 0) {
            throw new DatabaseDriverNotSupportedException(sprintf("The remote database driver is %s. Only MySQL is accepted.", $dbComponents['scheme']));
        }
        $credentials = [
            'driver' => 'mysql',
            'database' => ltrim($dbComponents['path'], '/'),
            'username' => $dbComponents['user'],
            'password' => $dbComponents['pass'],
            'host' => $dbComponents['host'],
            'port' => $dbComponents['port'],
        ];

        $credentials['port'] = $credentials['port'] ?: 3306;

        return $credentials;
    }

    public function loadPublicFilesPath()
    {
        return $this->send("print variable_get(\"file_public_path\", \"sites/default/files\");");
    }

    public function loadPrivateFilesPath()
    {
        return $this->send("print variable_get(\"file_private_path\", \"\");");
    }
}
