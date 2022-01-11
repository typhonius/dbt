<?php

namespace DBT\Backup\Local;

use DBT\Backup\Backup;
use DBT\Structures\Environment;
use phpseclib3\Net\SFTP;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class LocalFileOperations
{
    public Filesystem $filesystem;
    public SFTP $sftp;
    public Environment $environment;
    public Backup $backup;
    public $uniqueId;

    public function __construct(Filesystem $filesystem, SFTP $sftp, Environment $environment, Backup $backup)
    {
        $this->filesystem = $filesystem;
        $this->sftp = $sftp;
        $this->environment = $environment;
        $this->backup = $backup;
    }

    public function generateBackupLocation()
    {
        $path = sprintf("%s%s%s/%s", DBT_ROOT, $this->backup->getBackupDestination(), $this->environment->id, date('Y-m-d'));

        if (strpos($this->backup->getBackupDestination(), '/') === 0) {
            $path = sprintf("%s%s/%s", $this->backup->getBackupDestination(), $this->environment->id, date('Y-m-d'));
        }

        if (true === $this->backup->getUnique()) {
            $path = $this->getUniqueDirectoryName($path);
        }

        return $path;
    }

    public function prepareBackupLocation()
    {
        $path = $this->generateBackupLocation();
        $this->prepareDirectory($path);

        foreach (['code', 'db', 'files'] as $component) {
            $this->prepareDirectory($path . '/' . $component);
        }

        return $path;
    }

    public function recursiveDownload($listing, $path = '')
    {
        foreach ($listing as $name => $data) {
            if ($name == '.' || $name == '..') {
                continue;
            }
            $iterantPath = $path . $name;
            if (is_object($data)) {
                if ($data->type == 1) {
                    $local = $this->generateBackupLocation() . '/files/' . $iterantPath;
                    // @TODO change this to be $remote->loadPublicFilesPath();
                    $remote = $this->environment->path . '/sites/default/files/' . $iterantPath;
                    // echo sprintf('Backing up %s to %s', $remote, $local);
                    $this->sftp->get($remote, $local);
                }
            }
            if (is_array($data)) {
                $iterantPath .= '/';
                $this->prepareDirectory($this->generateBackupLocation() . '/files/' . $iterantPath);
                $this->recursiveDownload($data, $iterantPath);
            }
        }
    }

    public function prepareDirectory($path)
    {
        // @TODO use Symfony path here
        // Do a makeAbsolute() or a Path::canonicalize()
        if ($this->filesystem->exists($path) && !is_writeable($path)) {
            $this->filesystem->chmod($path, 0700);
        } else {
            $this->filesystem->mkdir($path, 0700);
        }

        return $this;
    }

    public function getUniqueDirectoryName($path)
    {
        // Copied from Filesystem->tempnam().
        // https://github.com/symfony/filesystem/blob/master/Filesystem.php
        if ($this->uniqueId) {
            return $this->uniqueId;
        }

        for ($i = 0; $i < 10; ++$i) {
            $uniqDir = $path . '-' . uniqid(mt_rand());

            if ($this->filesystem->exists($uniqDir)) {
                continue;
            }

            $this->uniqueId = $uniqDir;

            return $uniqDir;
        }

        throw new IOException('A unique file could not be created.');
    }
}
