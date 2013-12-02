<?php

namespace utils;

class File {
  public $files = array();

  public static function loadFiles($path, $mask) {
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
    if (is_dir($path) && is_writeable($path)) {
      return TRUE;
    }
    elseif (@mkdir($path, 0755, TRUE)) {
      return TRUE;
    }
    return FALSE;
  }

}
