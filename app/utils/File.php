<?php

namespace BackupOop\Utils;

use Symfony\Component\Filesystem\Filesystem;

class File {
  public $files = array();

  public static function loadFiles($path, $mask) {
    // http://symfony.com/doc/current/components/finder.html
    // @TODO use Finder
    if (is_dir($path) && $handle = @opendir($path)) {
      $files = array();
      while (FALSE !== ($filename = readdir($handle))) {
        if ($filename != '.' && preg_match($mask, $filename)) {
          $uri = "$path/$filename";
          $file = (object) array(
            'uri' => $uri,
            'path' => dirname($uri),
            'filename' => $filename,
          );
          $files[] = $file;
        }
      }
      return $files;
    }
    return array();
  }

  public static function checkDirectory($path) {
    $fs = new Filesystem();
    if ($fs->exists($path) && !is_writeable($path)) {
      $fs->chown($path, 0700);
    }
    else {
      $fs->mkdir($path, 0700);
    }
  }

}
