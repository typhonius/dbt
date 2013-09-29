<?php
/**
 * Created by JetBrains PhpStorm.
 * User: adam.malone
 * Date: 29/9/13
 * Time: 01:21 41
 * To change this template use File | Settings | File Templates.
 */

namespace utils;

class IncorrectSitenameException extends \Exception {
  public static function stderr() {
    var_dump('foobar');
  }
}