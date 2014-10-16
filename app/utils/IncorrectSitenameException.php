<?php

namespace BackupOop\Utils;

class IncorrectSitenameException extends \Exception {
  public static function stderr() {
    var_dump('foobar');
  }
}