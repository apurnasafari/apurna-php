<?php


#
# Apurna System Controller Class
#
class ApurnaSystemController {

  #
  # URL Pattern
  #
  private static $url_pattern;



  #
  # Init Application
  #
  public static function init() {

    # Load Apurna PHP
    include_once VENDOR_ROOT . '/apurna/logic.php' ;
    include_once VENDOR_ROOT . '/apurna/Console.php' ;

    if(DEBUG_FRAMEWORK == true) {
      Console::log('ApurnaSystemController->init()');
    }


    # Exception
    include_once SYSTEM_ROOT . '/lib/base/handler/ExceptionHandler.php' ;
    set_exception_handler(['ExceptionHandler', 'init']);


    self::getView();

  }



  #
  # Set URL Pattern
  #
  public static function setUrlPattern($url_pattern = []) {

    self::$url_pattern = $url_pattern;

  }



  #
  # Get URL Pattern
  #
  public static function getUrlPattern() {

    return self::$url_pattern;

  }



  #
  # Get View
  #
  public static function getView() {

    if(DEBUG_FRAMEWORK == true) {
      Console::log('ApurnaSystemController->getView()');
    }

    // Check request to find URI
    $request_uri = $_SERVER['REQUEST_URI'];

    // Remove get parameters
    if(strpos($request_uri, '?') > 0) {
      $request_uri = substr($request_uri, 0, strpos($request_uri, '?'));
    }

    if(DEBUG_FRAMEWORK == true) {
      Console::log('ApurnaSystemController->getView() $request_uri ' . strval($request_uri));
    }

    // Remove trailing slashes
    if(substr($request_uri, -1) == '/') {
      $request_uri = substr($request_uri, 0, -1);
    }

    // Default to root route
    if($request_uri == '') {
      $request_uri = '/';
    }

    // Convert to array
    $request_uri_parts = explode('/', $request_uri);

    $found       = false;
    $view_kwargs = [
      'user_id'  => null,
      'model_id' => null,
    ];

    // Referer is not always set. Default to empty
    if(isset($_SERVER['HTTP_REFERER']) == false) {
      $_SERVER['HTTP_REFERER'] = '';
    }

    # Request
    $view_request = [
      'referer'   => $_SERVER['HTTP_REFERER'],
      'remote_ip' => $_SERVER['REMOTE_ADDR'],
      'uri'       => $_SERVER['REQUEST_URI'],
      'method'    => strtolower($_SERVER['REQUEST_METHOD']),
      'route'     => null,
    ];

    // Find Matching Route
    foreach(self::getUrlPattern() as $app_name => $view_list) {
      foreach($view_list as $view_url => $view_options) {

        // Defaults
        if (empty($view_options) == true) {
          $view_options = [
            'route'    => null,
            'class'    => null,
            'action'   => null,
            'redirect' => null, // @wip Route where to redirect to
          ];
        }

        // @issue default_empty_array() not working on production
        // $view_options = default_empty_array($view_options, [
        //   'route'  => null,
        //   'class'  => null,
        //   'action' => null,
        // ]);

        if(DEBUG_FRAMEWORK == true) {
          Console::log('ApurnaSystemController->getView() $request_uri loop ' . print_r([
            '$request_uri'  => $request_uri,
            '$view_url'     => $view_url,
            // '$view_options' => $view_options,
          ], true));
        }


        if ($request_uri == $view_url) {

          $view_request['route'] = $view_options['route'];

          $found = true; // Exact match

        }
        else {

          // @wip pass for now, dynamic routing not working and should be disable for production
          continue;

          // $view_parts = explode('/', substr($view_url, 1));

          // Get Route Dynamic Values
          $view_dynamic_values = null;
          if (strpos($view_url, '{') > 0) {
            preg_match_all('/{\K[^}]*(?=})/m', $view_url, $values);
            $view_dynamic_values = $values; //[0];
          }

          if($view_dynamic_values != null) {

            if(DEBUG_FRAMEWORK == true) {
              Console::log('ApurnaSystemController->getView() Route Dynamic Values ' . print_r([
                '$view_url {'          => print_r(strpos($view_url, '{'), true),
                '$view_dynamic_values' => $view_dynamic_values,
                '$request_uri_parts'   => $request_uri_parts,
              ], true));
            }

            if(count($view_dynamic_values) == (count($request_uri_parts) - 2)) {

              // Dynamic route with matching dynamic value counts
              // Map dynamic routes to an array to be supplied to Action class
              foreach($view_dynamic_values as $key => $view_dynamic_value) {

                // if(DEBUG_FRAMEWORK == true) {
                //   Console::log('ApurnaSystemController->getView() view_dynamic_values ' . print_r([
                //     '$key'                => print_r($key, true),
                //     '$view_dynamic_value' => $view_dynamic_value,
                //   ], true));
                // }

                $view_kwargs[$view_dynamic_value] = $request_uri_parts[$key + 2];

              }

              $view_request['route'] = $view_options['route'];

              $found = true;

            }
          }

        }

        // Found Route
        if($found == true) {

          if(DEBUG_FRAMEWORK == true) {
            Console::log('ApurnaSystemController->getView() $app_name '   . strval($app_name));
            Console::log('ApurnaSystemController->getView() $view_class ' . strval($view_options['class']) . 'View');
            Console::log('ApurnaSystemController->getView() $view_action '. strval($view_options['action']));
            Console::log('ApurnaSystemController->getView() $view_kwargs '. print_r($view_kwargs, true));
          }

          $view_class  = $view_options['class'] . 'View'; // Root Namespace
          $view_action = $view_options['action'];

          break 2; // Exit view list loop

        }

      }
    }


    // Check if Class Exists, if not default to 404
    if(empty($view_class) == true) {
      $app_name    = 'core';
      $view_class  = 'CoreErrorView';
      $view_action = 'handle_invalid_view';
    }


    $include_class = SYSTEM_ROOT . '/app/' . $app_name . '/view/' . $view_class . '.php';
    if(is_file($include_class) == true) {
      include_once $include_class;
    }
    else {

      if(DEBUG_FRAMEWORK == true) {
        Console::log('ApurnaSystemController->getView() unable to load view ' . strval($include_class));
      }

      $app_name      = 'core';
      $view_class    = 'CoreErrorView';
      $view_action   = 'handle_invalid_view';
      $include_class = SYSTEM_ROOT . '/app/' . $app_name . '/view/' . $view_class . '.php';

      include_once $include_class;

    }


    // Create Action Class Instance
    $action_class = new $view_class($view_kwargs, $view_request);

    // Check if Function Exists, if not default to 404
    if(!method_exists($view_class, $view_action)) {
      $view_action = 'handle_invalid_view';
    }

    // Call Action Function
    $action_class->$view_action();

  }




}


