<?php

namespace DBT\Backup;

use DBT\Structures\Environment;
use DBT\Structures\Site;
use phpseclib3\Net\SSH2;

class ExecuteRemote
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

    public function send($command = '', $bootstrap = 'DRUPAL_BOOTSTRAP_FULL')
    {
        switch ($this->site->getVersion()) {
            case '7':
                $remoteCommand = "cd \"{$this->environment->path}\"; php -r '\$_SERVER[\"REMOTE_ADDR\"] = \"127.0.0.1\"; \$_SERVER[\"SCRIPT_NAME\"] = \"/\"; \$_SERVER[\"HTTP_HOST\"] = \"{$this->environment->url}\"; define(\"DRUPAL_ROOT\", \"{$this->environment->path}\"); require_once DRUPAL_ROOT . \"/includes/bootstrap.inc\"; drupal_bootstrap({$bootstrap}); {$command};'";
                break;
            case '8':
            case '9':
                $remoteCommand = "cd \"{$this->environment->path}\"; php -r 'use Symfony\Component\HttpFoundation\Request; use Drupal\Core\DrupalKernel; use Drupal\Core\Site\Settings; use Drupal\Core\Database\Database; \$_SERVER[\"REMOTE_ADDR\"] = \"127.0.0.1\"; \$_SERVER[\"SCRIPT_NAME\"] = \"/\"; \$_SERVER[\"HTTP_HOST\"] = \"{$this->environment->url}\"; define(\"DOCROOT\", \"{$this->environment->path}\"); \$autoloader = require_once DOCROOT . \"/../vendor/autoload.php\"; require_once DOCROOT . \"/core/includes/utility.inc\"; \$request = Request::createFromGlobals(); require_once DOCROOT . \"/core/includes/bootstrap.inc\"; DrupalKernel::bootEnvironment(); Settings::initialize(DRUPAL_ROOT, DrupalKernel::findSitePath(\$request), \$autoloader); {$command}'";
                break;
            default:
                throw new UnsupportedVersionException(sprintf("Unsupported Drupal version '%d'.", $this->site->getVersion()));
                break;
        }

        return $this->ssh->exec($remoteCommand);
    }

    public function downloadDatabase()
    {
        $credentials = $this->getDbCredentials();
        $password = escapeshellarg($credentials['password']);
        $dumpCommand = escapeshellcmd("mysqldump '-h{$credentials['host']}' '-P{$credentials['port']}' '-u{$credentials['username']}' '-p{$password}' '{$credentials['database']}'");

        return $this->ssh->exec($dumpCommand);
    }

    private function getDbCredentials()
    {
        $databases = unserialize($this->send("\$databases = Database::getConnectionInfo(); print serialize(\$databases);"));
        $credentials = $databases['default'];
        $credentials['port'] = $credentials['port'] ?: 3306;

        return $credentials;
    }

    public function loadPublicFilesPath()
    {
        switch ($this->getVersion()) {
            case '6':
            case '7':
                return $this->send("print variable_get(\"file_public_path\", \"sites/default/files\");");
                break;
            case '8':
            case '9':
                return $this->send("use Drupal\Core\StreamWrapper\PublicStream; print PublicStream::basePath();");
                break;
            default:
                // @TODO do we want to do this in a higher class?
                throw new UnsupportedVersionException(sprintf("Unsupported Drupal version '%d'.", $this->site->getVersion()));
                break;
        }
    }

    public function loadPrivateFilesPath()
    {
        switch ($this->getVersion()) {
            case '6':
            case '7':
                return $this->execRemoteCommand("print variable_get(\"file_private_path\", \"\");");
                break;
            case '8':
            case '9':
                return $this->execRemoteCommand("use Drupal\Core\StreamWrapper\PrivateStream; print PrivateStream::basePath();");
                break;
            default:
                // @TODO do we want to do this in a higher class?
                throw new UnsupportedVersionException(sprintf("Unsupported Drupal version '%d'.", $this->site->getVersion()));
                break;
        }
    }
}
