<?php

namespace DrupalBackup;

use DrupalBackup\Exception\DatabaseDriverNotSupportedException;
use DrupalBackup\Exception\Ssh2ConnectionException;
use DrupalBackup\Exception\UnsupportedVersionException;

/**
 * Class DrupalSite
 * @package DrupalBackup
 */
class DrupalSite
{

    // Error string from https://github.com/php/php-src/blob/master/main/main.c
    const PHPERRORS = [
        'Fatal error',
        'Catchable fatal error',
        'Warning',
        'Parse error',
        'Notice',
        'Strict Standards',
        'Deprecated',
        'Unknown error',
    ];

    /**
     * @var string $id
     */
    public $id;

    /**
     * @var string $backupPath
     */
    public $backupPath;

    /**
     * @var int version
     */
    protected $version;

    /**
     * @var string $hostname
     */
    protected $hostname;

    /**
     * @var int $port
     */
    protected $port;

    /**
     * @var string $user
     */
    protected $user;

    /**
     * @var string $key
     */
    protected $key;

    /**
     * @var string $path
     */
    protected $path;

    /**
     * @var string $url
     */
    protected $url;

    /**
     * @var bool $unique
     */
    protected $unique;

    /**
     * @var array $backup
     */
    protected $backup = [];

    /**
     * @var string $publicFilesPath
     */
    protected $publicFilesPath;

    /**
     * @var string $privateFilesPath
     */
    protected $privateFilesPath;

    /**
     * @var $keypass
     */
    private $keypass;

    /**
     * @var array $dbCredentials
     */
    private $dbCredentials;

    /**
     * DrupalSite constructor.
     * @param string $environmentId
     */
    public function __construct($environmentId)
    {
        $this->id = $environmentId;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getBackupPath()
    {
        return $this->backupPath;
    }

    /**
     * @param string $backupPath
     */
    public function setBackupPath($backupPath)
    {
        $this->backupPath = $backupPath;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @param string $hostname
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return bool $unique
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * @param bool $unique
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
    }

    /**
     * @return array
     */
    public function getBackupOptions()
    {
        return $this->backup;
    }

    /**
     * @param array $options
     */
    public function setBackupOptions($options = [])
    {
        if (empty($options)) {
            $this->backup = $this->getAllowedBackupOptions();

            return;
        }

        $this->backup = array_intersect($this->getAllowedBackupOptions(), $options);
    }

    /**
     * @param string $keypass
     */
    public function setKeypass($keypass)
    {
        $this->keypass = $keypass;
    }

    /**
     * @return bool
     */
    public function isKeypassEntered()
    {
        return isset($this->keypass);
    }

    /**
     * @param array $server
     */
    public function setServer($server)
    {
        $this->setHostname($server['hostname']);
        $this->setPort($server['port']);
        $this->setUser($server['user']);
        $this->setKey($server['key']);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function loadPublicFilesPath()
    {
        switch ($this->getVersion()) {
            case '6':
            case '7':
                return $this->execRemoteCommand("print variable_get(\"file_public_path\", \"sites/default/files\");", 'DRUPAL_BOOTSTRAP_VARIABLES');
                break;
            case '8':
                return $this->execRemoteCommand("use Drupal\Core\StreamWrapper\PublicStream; print PublicStream::basePath();");
                break;
            default:
                // @TODO do we want to do this in a higher class?
                throw new UnsupportedVersionException(sprintf("Unsupported Drupal version '%d'.", $this->getVersion()));
                break;
        }


    }

    /**
     * @return string
     */
    public function getPublicFilesPath()
    {
        return $this->publicFilesPath;
    }

    /**
     * @param string $publicFilesPath
     */
    public function setPublicFilesPath($publicFilesPath)
    {
        $this->publicFilesPath = $publicFilesPath;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function loadPrivateFilesPath()
    {
        // @TODO change for D8.

        switch ($this->getVersion()) {
            case '6':
            case '7':
                return $this->execRemoteCommand("print variable_get(\"file_private_path\", \"\");", 'DRUPAL_BOOTSTRAP_VARIABLES');
                break;
            case '8':
                return $this->execRemoteCommand("use Drupal\Core\StreamWrapper\PrivateStream; print PrivateStream::basePath();");
                break;
            default:
                // @TODO do we want to do this in a higher class?
                throw new UnsupportedVersionException(sprintf("Unsupported Drupal version '%d'.", $this->getVersion()));
                break;
        }
    }

    /**
     * @return string
     */
    public function getPrivateFilesPath()
    {
        return $this->privateFilesPath;
    }

    /**
     * @param string $privateFilesPath
     */
    public function setPrivateFilesPath($privateFilesPath)
    {
        $this->privateFilesPath = $privateFilesPath;
    }

    /**
     * @return array
     * @throws DatabaseDriverNotSupportedException
     * @throws \Exception
     */
    public function loadDbCredentials()
    {

        switch ($this->getVersion()) {
            case '6':
                $dbUrl = $this->execRemoteCommand("global \$db_url; print serialize(\$db_url);", 'DRUPAL_BOOTSTRAP_CONFIGURATION');
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
                break;
            case '7':
            case '8':
                $databases = unserialize($this->execRemoteCommand("global \$databases; print serialize(\$databases);"));
                $credentials = &$databases['default']['default'];

                if ($credentials['driver'] !== 'mysql') {
                    throw new DatabaseDriverNotSupportedException(sprintf("The remote database driver is %s. Only MySQL is accepted.", $credentials['driver']));
                }
                break;
            default:
                throw new UnsupportedVersionException(sprintf("Unsupported Drupal version '%d'.", $this->getVersion()));
                break;
        }

        $credentials['port'] = $credentials['port'] ?: 3306;

        return $credentials;
    }

    /**
     * @return array
     */
    public function getDbCredentials()
    {
        return $this->dbCredentials;
    }

    /**
     * @param array $dbCredentials
     */
    public function setDbCredentials($dbCredentials)
    {
        $this->dbCredentials = $dbCredentials;
    }

    /**
     * @param string $command
     * @param string $bootstrap
     * @return string
     * @throws UnsupportedVersionException
     * @throws \Exception
     */
    public function execRemoteCommand($command = '', $bootstrap = 'DRUPAL_BOOTSTRAP_FULL')
    {
        // @TODO now we're supporting D8, should we swap $command and $bootstrap?

        switch ($this->getVersion()) {
            case '7':
                $remoteCommand = "php -r '\$_SERVER[\"SCRIPT_NAME\"] = \"/\"; \$_SERVER[\"HTTP_HOST\"] = \"{$this->url}\"; define(\"DRUPAL_ROOT\", \"{$this->path}\"); require_once DRUPAL_ROOT . \"/includes/bootstrap.inc\"; drupal_bootstrap({$bootstrap}); {$command};'";
                break;
            case '8':
                $remoteCommand = "php -r 'use Symfony\Component\HttpFoundation\Request; use Drupal\Core\DrupalKernel; use Drupal\Core\Site\Settings; \$_SERVER[\"SCRIPT_NAME\"] = \"/\"; \$_SERVER[\"HTTP_HOST\"] = \"{$this->url}\"; define(\"DOCROOT\", \"{$this->path}\"); \$autoloader = require_once DOCROOT . \"/autoload.php\"; require_once DOCROOT . \"/core/includes/utility.inc\"; \$request = Request::createFromGlobals(); require_once DOCROOT . \"/core/includes/bootstrap.inc\"; DrupalKernel::bootEnvironment(); Settings::initialize(DRUPAL_ROOT, DrupalKernel::findSitePath(\$request), \$autoloader); {$command};'";
                break;
            default:
                throw new UnsupportedVersionException(sprintf("Unsupported Drupal version '%d'.", $this->getVersion()));
                break;
        }

        try {
            if (!@$connection = ssh2_connect($this->getHostname(), $this->getPort())) {
                throw new Ssh2ConnectionException(sprintf("Could not connect to %s on port %d as %s", $this->getHostname(), $this->getPort(), $this->getUser()));
            }
        } catch (\Exception $e) {
            throw $e;
        }

        try {
            if (!@ssh2_auth_pubkey_file($connection, $this->getUser(), $this->getKey().'.pub', $this->getKey(), $this->keypass)) {
                throw new Ssh2ConnectionException(sprintf("Could not successfully authenticate key %s. Was the public key password correct?", $this->getKey()));
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $stream = ssh2_exec($connection, $remoteCommand);
        stream_set_blocking($stream, true);
        $streamStdio = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);

        // Read the stream output and trim characters from it.
        $streamOut = trim(stream_get_contents($streamStdio));

        // Detect warnings and errors from return streams.
        foreach (self::PHPERRORS as $e) {
            if (strpos($streamOut, $e) === 0) {
                throw new Ssh2ConnectionException($streamOut);
            }
        }

        return $streamOut;
    }

    /**
     * @return array
     */
    private function getAllowedBackupOptions()
    {
        return ['db', 'code', 'files'];
    }
}
