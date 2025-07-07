<?php


#
# Apurna Model Class
#
class ApurnaView {

  #
  # Request/Response
  #
  public $request;
  public $request_kwargs;
  public $request_method_enabled;
  public $response_content_type;
  public $response_status_code;

  #
  # Model
  #
  private $model_table;
  private $model_id;
  private $model_request_id;
  private $model_instance;
  public $model_render;
  public $model_render_options;
  public $model_render_instance;
  public $model_render_params;

  #
  # User
  #
  public $user_id;
  public $user_instance;

  #
  # Template
  #
  public $template_name;
  public $template_scripts;

  #
  # System
  #
  public $system_title;
  public $system_tag;
  public $system_css_class;

  #
  # View
  #
  public $view_method;
  public $view_title;
  public $view_name;
  public $view_type;
  public $view_lock;




  /**
   * Class Constructor
   */
  public function __construct($kwargs = [], $request = []) {

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->__construct()');
      Console::log(get_class($this) . '->__construct() kwargs ' . print_r($kwargs, true));
      Console::log(get_class($this) . '->__construct() request ' . print_r($request, true));
    }

    #
    # Request
    #
    $this->request                = $request;
    $this->request_kwargs         = $kwargs;
    $this->request_method_enabled = [
      'get'     => true,
      'post'    => true,
      'put'     => true,
      'patch'   => true,
      'clone'   => true,
      'load'    => true,
      'reload'  => true,
      'archive' => true,
      'discard' => true,
      'head'    => true,
      'options' => true,
    ];

    #
    # Response
    #
    $this->response_content_type = 'text/html; charset=UTF-8';
    $this->response_status_code  = 200;

    #
    # Model
    #
    $this->model_table      = null;
    $this->model_id         = null; # Set by get_model_id()
    $this->model_request_id = null; # Set by get_request_model_id()
    $this->model_instance   = null;

    #
    # Model Render
    #
    $this->model_render          = null;
    $this->model_render_options  = null;
    $this->model_render_instance = null;
    $this->model_render_params   = [
      'Attributes',
      'Meta',
    ];

    #
    # User
    #
    $this->user_id       = null; # Set by get_user_id()
    $this->user_instance = null; # Set by get_user_instance()

    #
    # Template
    #
    $this->template_name    = null;
    $this->template_scripts = null;

    #
    # System
    #
    $this->system_title     = null;
    $this->system_tag       = null; # Max 4 Chars
    $this->system_css_class = null;

    #
    # View
    #
    $this->view_method = null; # @note This is typically follows get|post methods, but can be overriden manually for edge cases.
    $this->view_title  = null;
    $this->view_name   = null;
    $this->view_type   = null;
    $this->view_lock   = false;

    $this->init();
    $this->dispatch();

  }




  #
  # Init
  #
  function init() {

    # Call data setting functions
    $this->get_model_instance();
    $this->get_model_id();
    $this->get_user_instance();
    $this->get_user_id();
    $this->generate_view_title();
    $this->get_view_lock();

  }




  #
  # Dispatch
  #
  function dispatch() {

    session_start();

    $response = []; # Init response kwargs

    # Return response from user authentication
    # $auth_response = $this->session_auth();
    # if ($auth_response != null) {
    #   return $auth_response;
    # }

    $this->view_method = $this->request['method'];

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->dispatch() view_method: ' . $this->view_method);
    }

    if ($this->request_method_enabled[$this->view_method] == true) {

      $method_call = 'method_' . $this->view_method;

      if(DEBUG_VIEW == true) {
        Console::log(get_class($this) . '->dispatch() method_call: ' . $method_call);
      }

      if (method_exists($this, $method_call) == false) {
        if(DEBUG_VIEW == true) {
          Console::log(get_class($this) . '->dispatch() method_exists false: ' . $method_call);
        }
        return $this->handle_invalid_view();
      }

      try {
        return $this->$method_call();
      }
      catch(Exception $exception) {
        $this->handle_exception(get_class($this) . '->dispatch() error calling ' . $method_call . '()', $exception);
      }

    }
    else {
      return $this->send_response_method_not_allowed();
    }

    # Return Redirect Response when id is provided in url (show/edit) and corresponding model instance is None
    # if ($this->check_valid_view() == False) {
    #   return $this->handle_invalid_view();
    # }


    # Call preprocess before calling method
    # $preprocess_response = self.preprocess();
    # if ($preprocess_response != null) {
    #   return $preprocess_response;
    # }

  }




  #
  # Session Auth
  #
  function session_auth() {

  }




  #
  # Session Auth Permission
  #
  function session_auth_permission() {

  }




  #
  # Preprocess
  # Called before any method function (get/post/put/...)
  #
  function preprocess() {

  }




  #
  # Process
  # To be manually called per view use
  #
  function process() {

  }




  #
  # Method Get
  # @use  The HTTP `GET` method requests a representation of the specified resource.
  #       Requests using GET should only be used to request data (they shouldn't include data).
  # @docs https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/GET
  #
  function method_get() {

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->method_get() called');
    }

    return $this->render();

  }




  #
  # Method Post
  # @use  The HTTP `POST` method sends data to the server. The type of the body of the request is indicated by the Content-Type header.
  #       In the CRUD concept, this is create, update and delete.
  # @docs https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/POST
  # @note Used to save forms as FormData and general AJAX requests for non-FormViews
  #
  function method_post() {

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->method_post() called');
    }

    return $this->render();

  }




  #
  # Method Put
  # @use  The HTTP `PUT` request method creates a new resource or replaces a representation of the target resource with the request payload.
  #       In the CRUD concept, this is create.
  # @docs https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/PUT
  # @note Used to save forms as JSON
  #
  function method_put() {

  }




  #
  # Method Patch
  # @use  The HTTP `PATCH` request method applies partial modifications to a resource.
  #       In the CRUD concept, this is update.
  # @docs https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/PATCH
  #
  function method_patch() {

  }




  #
  # Method Clone
  # @use  The HTTP `CLONE` request method loads the specified resource with a payload.
  # @note Non-standard HTTP METHOD
  #
  function method_clone() {

  }




  #
  # Method Load
  # @use  The HTTP `LOAD` request method loads the specified resource with a payload.
  # @note Non-standard HTTP METHOD
  #
  function method_load() {

  }




  #
  # Method Reload
  # @use  The HTTP `RELOAD` request method loads the specified resource with a payload.
  # @note Non-standard HTTP METHOD
  #
  function method_reload() {

  }




  #
  # Method Archive
  # @use  The HTTP `ARCHIVE` request method archives the specified resource.
  # @note Non-standard HTTP METHOD
  #
  function method_archive() {

  }




  #
  # Method Discard
  # @use  The HTTP `DISCARD` request method discards the specified resource.
  # @docs https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/DELETE
  # @note Non-standard HTTP METHOD
  #
  function method_discard() {

  }




  #
  # Method Head
  # @use  The HTTP `HEAD` method requests the headers that would be returned if the HEAD request's URL was instead requested with the HTTP GET method.
  #       For example, if a URL might produce a large download, a HEAD request could read its Content-Length header to check the filesize without actually downloading the file.
  # @docs https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/HEAD
  #
  function method_head() {

  }




  #
  # Method Options
  # @use  The HTTP `OPTIONS` method requests permitted communication options for a given URL or server. A client can specify a URL with this method, or an asterisk (*) to refer to the entire server.
  # @docs https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS
  # @note Return JSON explaining payloads and/or API usage
  #
  function method_options() {

  }




  #
  # Get Request JSON from Request Body
  #
  function get_request_json() {

  }




  #
  # Get Model Instance
  #
  function get_model_instance() {

  }




  #
  # Get Model ID
  #
  function get_model_id() {

    if(is_array($this->request_kwargs) == true) {
      $this->model_id = $this->request_kwargs['model_id'];
    }

  }




  #
  # Get Model Request ID
  #
  function get_request_model_id() {

    if(is_array($this->request_kwargs) == true) {
      $this->model_request_id = $this->request_kwargs['model_id'];
    }

  }




  #
  # Get User Instance
  #
  function get_user_instance() {

  }




  #
  # Get User ID
  #
  function get_user_id() {

    if(is_array($this->request_kwargs) == true) {
      $this->user_id = $this->request_kwargs['user_id'];
    }

  }




  #
  # Generate View Title
  #
  function generate_view_title() {

  }




  #
  # Get View Lock
  #
  function get_view_lock() {

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->get_view_lock() called');
    }

  }




  #
  # Send Response Empty
  #
  function send_response_empty() {

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->send_response_empty() called');
    }

  }




  #
  # Send Response Method Not Allowed Response
  #
  function send_response_method_not_allowed() {

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->send_response_method_not_allowed() called');
    }

  }




  #
  # Render
  #
  function render($response = []) {

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->render() called');
    }

    header('Content-Type: ' . $this->response_content_type);
    http_response_code($this->response_status_code);


    try {
      $response = $this->render_data($response);
    }
    catch(Exception $exception) {
      $this->handle_exception(get_class($this) . '->render() error calling render_data()', $exception);
    }

    try {
      $response = $this->render_template($response);
    }
    catch(Exception $exception) {
      $this->handle_exception(get_class($this) . '->render() error calling render_template()', $exception);
    }

  }




  #
  # Render Template
  #
  function render_template($response = []) {

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->render_template() called');
      Console::log(get_class($this) . '->render_template() template_name: ' . $this->template_name);
    }

    // Exit if no template
    if(empty($this->template_name) == true) {
      if(DEBUG_VIEW == true) {
        Console::log(get_class($this) . '->render_template() No template');
      }
      return $response;
    }


    require VENDOR_ROOT . '/smarty3/SmartyBC.class.php';

    $smarty = new SmartyBC;
    $smarty->template_dir = SYSTEM_ROOT . '/app';
    $smarty->compile_dir  = SYSTEM_ROOT . '/cache/template';


    // Assign response and data
    foreach($response as $key => $value) {
      if(empty($key) == false) {
        $smarty->assign($key, $value);
      }
    }


    $smarty->assign('content', $smarty->fetch($this->template_name));
    $smarty->display('core/template/base/layout.tpl');

  }




  #
  # Render Data
  #
  function render_data($response = []) {

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->render_data() called');
    }

    # Response Defaults
    $response = [

      'content_type'     => $this->response_content_type,
      'status_code'      => $this->response_status_code,

      'system_title'     => $this->system_title,
      'system_css_class' => $this->system_css_class,

      'view_class'       => get_class($this),
      'view_type'        => $this->view_type,
      'view_title'       => $this->view_title,
      'view_name'        => $this->view_name,
      'view_lock'        => $this->view_lock,

      'options'          => [
        'model_instance' => $this->model_instance,
        'model_id'       => $this->model_id,
        'user_instance'  => $this->user_instance,
        'user_id'        => $this->user_id,
      ],
      'data'             => [],
      'payload'          => [],

    ];

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->render_data() ' . print_r($response, true));
    }

    return $response;

  }




  #
  # Render Data
  #
  function render_payload() {

  }




  #
  # Assign Data
  #
  function assign_data() {

  }




  #
  # Assign Data
  #
  function assign_payload() {

  }




  #
  # Download
  #
  function download() {

  }




  #
  # Handle Exception
  #
  function handle_exception($message, $exception) {

    if(DEBUG_VIEW == true) {
      Console::log($message . ': ' . $exception->getMessage());
    }

  }




  #
  # Check Valid View
  #
  function check_valid_view() {

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->check_valid_view() called');
    }

  }




  #
  # Handle Invalid View
  #
  function handle_invalid_view() {

    if(DEBUG_VIEW == true) {
      Console::log(get_class($this) . '->handle_invalid_view() called');
    }

  }




}


