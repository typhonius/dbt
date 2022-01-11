<?php

namespace DBT\Backup\Remote;

use DBT\Exception\DatabaseDriverNotSupportedException;

class ExecuteRemote8 extends ExecuteRemoteBase
{

    public function send($command = '', $bootstrap = 'DRUPAL_BOOTSTRAP_FULL')
    {
        $remoteCommand = "cd \"{$this->environment->path}\"; php -r 'use Symfony\Component\HttpFoundation\Request; use Drupal\Core\DrupalKernel; use Drupal\Core\Site\Settings; use Drupal\Core\Database\Database; \$_SERVER[\"REMOTE_ADDR\"] = \"127.0.0.1\"; \$_SERVER[\"SCRIPT_NAME\"] = \"/\"; \$_SERVER[\"HTTP_HOST\"] = \"{$this->environment->url}\"; define(\"DOCROOT\", \"{$this->environment->path}\"); \$autoloader = require_once DOCROOT . \"/../vendor/autoload.php\"; require_once DOCROOT . \"/core/includes/utility.inc\"; \$request = Request::createFromGlobals(); require_once DOCROOT . \"/core/includes/bootstrap.inc\"; DrupalKernel::bootEnvironment(); Settings::initialize(DRUPAL_ROOT, DrupalKernel::findSitePath(\$request), \$autoloader); {$command}'";
        
        return $this->ssh->exec($remoteCommand);
    }

    protected function getDbCredentials()
    {
        $databases = unserialize($this->send("\$databases = Database::getConnectionInfo(); print serialize(\$databases);"));
        $credentials = &$databases['default'];

        if ($credentials['driver'] !== 'mysql') {
            throw new DatabaseDriverNotSupportedException(sprintf("The remote database driver is %s. Only MySQL is accepted.", $credentials['driver']));
        }

        $credentials['port'] = $credentials['port'] ?: 3306;

        return $credentials;
    }

    public function loadPublicFilesPath()
    {
        return $this->send("use Drupal\Core\StreamWrapper\PublicStream; print PublicStream::basePath();");
    }

    public function loadPrivateFilesPath()
    {
        return $this->send("use Drupal\Core\StreamWrapper\PrivateStream; print PrivateStream::basePath();");
    }
}
