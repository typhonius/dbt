<?php
/**
 * Created by JetBrains PhpStorm.
 * User: adam.malone
 * Date: 21/9/13
 * Time: 03:44 50
 * To change this template use File | Settings | File Templates.
 */

namespace backup;


class File {
  public $files = array();

  public function loadFiles($path, $mask) {
    if (is_dir($path) && $handle = @opendir($path)) {
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
  }

  public function checkDirectory($path) {
    if (is_dir($path) && is_writeable($path)) {
      return TRUE;
    }
    elseif (mkdir($path, 0755, TRUE)) {
      return TRUE;
    }
    return FALSE;
  }

}