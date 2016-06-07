<?php

namespace DrupalBackup;

use DrupalBackup\Exception\Ssh2ConnectionException;

/**
 * Class DrupalSite
 * @package DrupalBackup
 */
class DrupalSite
{

    /**
     * @var string $id
     */
    public $id;

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
    protected $backup = array();

    /**
     * @var $keypass
     */
    private $keypass;

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
    public function getUnique()
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
    public function setBackupOptions($options = array())
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
     * @param string $bootstrap
     * @param string $command
     * @return string
     * @throws \Exception
     * @throws Ssh2ConnectionException
     */
    public function execRemoteCommand($bootstrap = 'DRUPAL_BOOTSTRAP_FULL', $command = '')
    {
        $remoteCommand = "php -r '\$_SERVER[\"SCRIPT_NAME\"] = \"/\"; \$_SERVER[\"HTTP_HOST\"] = \"{$this->url}\"; define(\"DRUPAL_ROOT\", \"{$this->path}\"); require_once DRUPAL_ROOT . \"/includes/bootstrap.inc\"; drupal_bootstrap({$bootstrap}); {$command};'";
        $connection = ssh2_connect($this->getHostname(), $this->getPort());

        // @TODO change this to authentication and connection
        try {
            if (!@ssh2_auth_pubkey_file($connection, $this->getUser(), $this->getKey().'.pub', $this->getKey(), $this->keypass)) {
                throw new Ssh2ConnectionException(sprintf("Could not connect to %s on port %d as %s", $this->getHostname(), $this->getPort(), $this->getUser()));
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $stream = ssh2_exec($connection, $remoteCommand);
        stream_set_blocking($stream, true);
        $streamOut = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);

        return stream_get_contents($streamOut);
    }

    /**
     * @return array
     */
    private function getAllowedBackupOptions()
    {
        return ['db', 'code', 'files'];
    }
}
