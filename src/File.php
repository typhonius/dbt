<?php

namespace DrupalBackup;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * Class File
 * @package DrupalBackup
 */
class File
{
    /**
     * @var array $files
     */
    public $files = [];

    /**
     * @param string $path
     * @param string $mask
     * @return $finder Finder
     */
    public static function loadFiles($path, $mask)
    {
        $finder = new Finder();
        $finder->files()->in($path)->name($mask);

        return $finder;
    }

    /**
     * @param string $path
     * @param bool   $unique
     * @return string $path
     */
    public static function prepareDirectory($path, $unique = false)
    {
        $fs = new Filesystem();

        if (true === $unique) {
            $path = self::getUniqueDirectoryName($path);
        }

        if ($fs->exists($path) && !is_writeable($path)) {
            $fs->chmod($path, 0700);
        } else {
            $fs->mkdir($path, 0700);
        }

        return $path;
    }

    /**
     * @param string $path
     * @return string $uniqDir
     */
    public static function getUniqueDirectoryName($path)
    {
        // Copied from Filesystem->tempnam().
        // https://github.com/symfony/filesystem/blob/master/Filesystem.php
        $fs = new Filesystem();

        for ($i = 0; $i < 10; ++$i) {
            $uniqDir = $path.'-'.uniqid(mt_rand());

            if ($fs->exists($uniqDir)) {
                continue;
            }

            return $uniqDir;
        }

        throw new IOException('A unique file could not be created.');
    }

    /**
     * @param SplFileInfo $file
     * @return string
     */
    public static function parse(SplFileInfo $file)
    {
        return Yaml::parse($file->getContents());
    }
}
