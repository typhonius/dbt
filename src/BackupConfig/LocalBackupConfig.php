<?php

namespace DrupalBackup\BackupConfig;

use DrupalBackup\DrupalSite;
use DrupalBackup\Exception\DatabaseDriverNotSupportedException;
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
    public function getBackupLocation(DrupalSite $site, $component)
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
                $backupPath = $this->getBackupLocation($site, $component);
                $publicFiles = $site->execRemoteCommand('DRUPAL_BOOTSTRAP_VARIABLES', "print variable_get(\"file_public_path\", \"sites/default/files\");");
                // @TODO create external command class
                $return['command'] = escapeshellcmd("rsync -e 'ssh -p {$site->getPort()}' -aPhL -f '+ */' -f '+ */files/***' -f '- *' {$site->getUser()}@{$site->getHostname()}:{$site->getPath()}/{$publicFiles} {$backupPath}");
                $return['backup_location'] = $backupPath;
                break;

            case 'code':
                $backupPath = $this->getBackupLocation($site, $component);
                $return['command'] = escapeshellcmd("rsync -e 'ssh -p {$site->getPort()}' -aPhL -f '- sites/*/files' -f '- .git' {$site->getUser()}@{$site->getHostname()}:{$site->getPath()}/ {$backupPath}");
                $return['backup_location'] = $backupPath;
                break;

            case 'db':
                $backupPath = $this->getBackupLocation($site, $component);
                // Get the DB credentials
                $databases = unserialize($site->execRemoteCommand('DRUPAL_BOOTSTRAP_CONFIGURATION', "global \$databases; print serialize(\$databases);"));
                $credentials = &$databases['default']['default'];

                if ($credentials['driver'] !== 'mysql') {
                    throw new DatabaseDriverNotSupportedException(sprintf("The remote database driver is %s. Only MySQL is accepted.", $credentials['driver']));
                }
                $credentials['port'] = $credentials['port'] ?: 3306;
                $dumpCommand = escapeshellcmd("mysqldump '-h{$credentials['host']}' '-P{$credentials['port']}' '-u{$credentials['username']}' '-p{$credentials['password']}' '{$credentials['database']}'");
                $return['command'] = "ssh -p{$site->getPort()} {$site->getUser()}@{$site->getHostname()} '{$dumpCommand} | gzip -c' > {$backupPath}/{$site->getId()}.sql.gz";
                $return['backup_location'] = $backupPath;
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
