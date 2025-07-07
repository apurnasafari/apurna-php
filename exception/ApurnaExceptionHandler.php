<?php


#
# Apurna Exception Handler Class
#
class ApurnaExceptionHandler {

  private static $data;
  private static $notify;




  #
  # Init Handler
  #
  public static function init($exception = null) {

    self::$data = [
      'meta'     => [
        'environment' => null,
        'server'      => null,
        'referer'     => null,
        'uri'         => null,
        'url_name'    => null,
        'message'     => null,
      ],
      'traces'   => null,
      'request'  => null,
    ];

    self::$notify = [
      'mail' => false,
      'log'  => false,
    ];

    self::$data['meta']    = self::get_data_meta($exception);
    self::$data['traces']  = self::get_data_traces($exception);
    self::$data['request'] = self::get_data_request($exception);

    $details = print_r(array(self::$data), true);

    // Write to log
    error_log($details . "\n", 3, SYSTEM_ROOT . '/log/exception.log');

    $message = "<pre>" . $exception->getMessage() . "\n" . $details . "</pre>";

    // Email error to developer
    if(DEBUG_EXCEPTION == true) {
      print_r($message);
    }
    else {
      // Email::send(
      //   DEV_EMAIL, null,
      //   SITE_EMAIL, SITE_TITLE,
      //   SITE_TITLE . " Exception Error",
      //   $message
      // );
    }
    exit;

  }




  #
  # Get meta with request and exception
  #
  public static function get_data_meta($exception = null) {

    return [
      'environment' => ENVIRONMENT,
      'server'      => SERVER_CURRENT,
      'referer'     => $_SERVER['HTTP_REFERER'],
      'remote_ip'   => $_SERVER['REMOTE_ADDR'],
      'uri'         => $_SERVER['REQUEST_URI'],
      'method'      => strtolower($_SERVER['REQUEST_METHOD']),
      'message'     => $exception->getMessage(),
    ];

  }




  #
  # Get Data Traces
  #
  public static function get_data_traces($exception = null) {

    return $exception->getTrace();

  }




  #
  # Get request and remove unnecessary or private data
  #
  public static function get_data_request($exception = null) {

    // @todo Strip out any security information


    // Sessions are not always active. So only get values if active.
    // @docs https://www.php.net/manual/en/function.session-status.php
    $session = null;
    if (session_status() === PHP_SESSION_ACTIVE) {
      $session = $_SESSION;
    }

    return [
      'data'    => $_REQUEST,
      'session' => $session,
      'cookie'  => $_COOKIE,
      'server'  => $_SERVER
    ];

  }




}


