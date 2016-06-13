<?php

namespace DrupalBackup\BackupConfig;

use DrupalBackup\DrupalSite;
use DrupalBackup\Exception\InvalidComponentException;
use DrupalBackup\File;

/**
 * Class LocalBackupConfig
 * @package DrupalBackup\BackupConfig
 */
class LocalBackupConfig extends AbstractDrupalConfigBase
{

    /**
     * {@inheritdoc}
     */
    protected $backupPath;

    /**
     * {@inheritdoc}
     */
    public function prepareBackupLocation(DrupalSite $site, $component)
    {
        if (!$this->getBackupPath()) {
            $sitePath = $site->getId().'/'.date('Y-m-d');

            $localConfig = $this->loadConfig(self::CONFIG_DIR.'/local');
            if ($path = $localConfig['local']['backup']) {
                $path .= '/'.$sitePath;
            } else {
                $path = ROOT_DIR.'/backups/'.$sitePath;
            }

            // Create interim directory with unique name if needed.
            $backupPath = File::prepareDirectory($path, $site->getUnique());

            // Temporarily cache the backup path for this particular Drupal site so we can store everything within
            // the same uniquely named directory.
            $this->setBackupPath($backupPath);
        }

        $path = $this->getBackupPath();

        switch ($component) {
            case 'code':
            case 'files':
            case 'db':
                $path .= "/${component}";
                break;
            default:
                throw new InvalidComponentException(sprintf("The backup component '%s' is invalid. Only code, files and db are accepted.", $component));
        }

        File::prepareDirectory($path);

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackupCommand(DrupalSite $site, $component)
    {

        $return = [];

        switch ($component) {
            case 'files':
                $this->prepareBackupLocation($site, $component);
                $site->setPublicFilesPath($site->loadPublicFilesPath());
                // $site->setPrivateFilesPath($site->loadPrivateFilesPath());

                $return[] = escapeshellcmd("rsync -e 'ssh -p {$site->getPort()}' -aPhL -f '+ */' -f '+ */files/***' -f '- *' {$site->getUser()}@{$site->getHostname()}:{$site->getPath()}/{$site->getPublicFilesPath()} {$this->getBackupPath()}");
                break;

            case 'code':
                $this->prepareBackupLocation($site, $component);
                $return[] = escapeshellcmd("rsync -e 'ssh -p {$site->getPort()}' -aPhL -f '- sites/*/files' -f '- .git' {$site->getUser()}@{$site->getHostname()}:{$site->getPath()}/ {$this->getBackupPath()}");
                break;

            case 'db':
                $this->prepareBackupLocation($site, $component);
                $dbCredentials = $site->loadDbCredentials();

                $dumpCommand = escapeshellcmd("mysqldump '-h{$dbCredentials['host']}' '-P{$dbCredentials['port']}' '-u{$dbCredentials['username']}' '-p{$dbCredentials['password']}' '{$dbCredentials['database']}'");
                $return[] = "ssh -p{$site->getPort()} {$site->getUser()}@{$site->getHostname()} '{$dumpCommand} | gzip -c' > {$this->getBackupPath()}/{$site->getId()}.sql.gz";
                break;
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function setBackupPath($path)
    {
        $this->backupPath = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackupPath()
    {
        return $this->backupPath;
    }
}
