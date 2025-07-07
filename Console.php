<?php


#
# Apurna Console Class
#
class Console {




  #
  # Write
  #
  private static function write($key = null, $value = null, $log_file = 'console') {

    $output = print_r($key, true);

    if(is_empty($value) == false) {
      $output .= ': ' . print_r($value, true);
    }

    error_log($output . "\n", 3, SYSTEM_ROOT . '/log/' . $log_file . '.log');

  }




  #
  # Log
  #
  public static function log($key = null, $value = null) {

    self::write($key, $value);

  }




  #
  # Debug
  #
  public static function debug($key = null, $value = null) {

    if(DEBUG_DEV == true) {
      self::write($key, $value, 'debug');
    }

  }




  #
  # SQL
  #
  public static function sql($key = null, $value = null) {

    if(DEBUG_DATABASE == true) {
      self::write($key, $value, 'sql');
    }

  }




  #
  # Exception
  #
  public static function exception($key = null, $value = null) {

    if(DEBUG_EXCEPTION == true) {
      self::write($key, $value, 'exception');
    }

  }




}



