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
    public function prepareBackupLocation(DrupalSite $site, $component)
    {
        if (!$site->getBackupPath()) {
            $sitePath = $site->getId().'/'.date('Y-m-d');

            $localConfig = $this->loadConfig(self::CONFIG_DIR.'/local');
            if ($path = $localConfig['local']['backup']) {
                $path .= '/'.$sitePath;
            } else {
                $path = ROOT_DIR.'/backups/'.$sitePath;
            }

            // Create interim directory with unique name if needed.
            $backupPath = File::prepareDirectory($path, $site->isUnique());

            // Temporarily cache the backup path for this particular Drupal site so we can store everything within
            // the same uniquely named directory.
            $site->setBackupPath($backupPath);
        }

        $path = $site->getBackupPath();

        switch ($component) {
            case 'code':
            case 'files':
            case 'db':
                $path .= "/${component}";
                break;
            default:
                throw new InvalidComponentException(sprintf("The backup component '%s' is invalid. Only code, files and db are accepted.", $component));
        }

        return File::prepareDirectory($path);

    }

    /**
     * {@inheritdoc}
     */
    public function getBackupCommand(DrupalSite $site, $component)
    {

        $return = [];

        switch ($component) {
            case 'files':
                $backupDir = $this->prepareBackupLocation($site, $component);
                $site->setPublicFilesPath($site->loadPublicFilesPath());
                $site->setPrivateFilesPath($site->loadPrivateFilesPath());

                $return[] = escapeshellcmd("rsync -e 'ssh -p {$site->getPort()}' -aPhL -f '+ */' -f '+ */files/***' -f '- *' {$site->getUser()}@{$site->getHostname()}:{$site->getPath()}/{$site->getPublicFilesPath()} {$backupDir}");
                break;

            case 'code':
                $backupDir = $this->prepareBackupLocation($site, $component);
                // @TODO do we want to get the public files path and remove it rather than sites/*/files?
                $return[] = escapeshellcmd("rsync -e 'ssh -p {$site->getPort()}' -aPhL -f '- sites/*/files' -f '- .git' {$site->getUser()}@{$site->getHostname()}:{$site->getPath()}/ {$backupDir}");
                break;

            case 'db':
                $backupDir = $this->prepareBackupLocation($site, $component);
                $dbCredentials = $site->loadDbCredentials();

                $dumpCommand = escapeshellcmd("mysqldump '-h{$dbCredentials['host']}' '-P{$dbCredentials['port']}' '-u{$dbCredentials['username']}' '-p{$dbCredentials['password']}' '{$dbCredentials['database']}'");
                $return[] = "ssh -p{$site->getPort()} {$site->getUser()}@{$site->getHostname()} '{$dumpCommand} | gzip -c' > {$backupDir}/{$site->getId()}.sql.gz";
                break;
        }

        return $return;
    }
}
