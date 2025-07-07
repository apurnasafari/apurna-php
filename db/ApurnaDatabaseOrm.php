<?php


#
# Apurna Database ORM Class
#
class ApurnaDatabaseOrm {

  protected static $_database_connection = null;
  protected static $_database_name       = null;
  protected static $_database_table      = null;
  protected static $_database_model      = null;



  #
  # Start Connection
  #
  public static function connect($database_name = 'default') {

    static::$_database_name = $database_name;

    $database = DATABASE[$database_name];

    static::$_database_connection = mysqli_init();

    // Connect with SSL
    if ($database['TLS']['ENABLED'] === true) {
      mysqli_ssl_set(
        static::$_database_connection,
        $database['TLS']['CLIENT_KEY'],
        $database['TLS']['CLIENT_CERT'],
        $database['TLS']['CA_CERT'],
        NULL,
        NULL
      );
    }

    // Connect to Server
    mysqli_real_connect(
      static::$_database_connection,
      $database['HOST'],
      $database['USER'],
      $database['PASS'],
      $database['NAME'],
      $database['PORT']
    );

    // Check Connection
    if (self::is_connected() == false) {
      self::throw_exception('->connect() Unable to connect to database: ' . print_r($database, true));
    }

    // Set Character Set
    mysqli_set_charset(static::$_database_connection, 'utf8');

  }



  /**
   * Check if Connected
   */
  public static function is_connected() {

    return mysqli_ping(static::$_database_connection);

  }



  #
  # Close Connection
  #
  public static function close() {

    mysqli_close(static::$_database_connection);

  }



  #
  # Send Query
  # @param str $query
  #
  public static function query($query) {

    // Check if connected
    if (self::is_connected() == false) {
      self::throw_exception('->query() Database has lost connection');
    }

    // Send SQL Query
    if (($result = mysqli_query(static::$_database_connection, $query))) {
      return $result;
    }
    else {

      $error = [
        'Message' => mysqli_error(static::$_database_connection),
        'Query'   => $query
      ];

      self::throw_exception('->query() Database Error: ' . print_r($error, true));
    }

  }



  #
  # Send Multi Query
  # @param str $query
  #
  public static function multi_query($query) {

    // Check if connected
    if (self::is_connected() == false) {
      self::throw_exception('->multi_query() Database has lost connection');
    }

    // Send SQL Query
    if (($result = mysqli_multi_query(static::$_database_connection, $query))) {
      return $result;
    }
    else {

      $error = [
        'Message' => mysqli_error(static::$_database_connection),
        'Query'   => $query
      ];

      self::throw_exception('->multi_query() Database Error: ' . print_r($error, true));

    }

  }



  #
  # Dispense new Model Instance with database defaults
  #
  public static function dispense() {

    if (empty(self::$_database_table) == true) {
      self::throw_exception('->dispense() self::$_database_table is not set');
    }
    if (empty(self::$_database_model) == true) {
      self::throw_exception('->dispense() self::$_database_model is not set');
    }

    $result = self::query('DESCRIBE `' . self::$_database_table . '`');
    $data   = new stdClass();

    while ($result_list = mysqli_fetch_assoc($result)) {

      $field = $result_list['Field'];
      $data  = '';

      if (empty($data) == true) {
        if ($result_list['Default'] == null && $result_list['Null'] == 'YES') {
          $data = null;
        }
        elseif ($result_list['Default'] != '' && $result_list['Default'] !== null) {
          $data = $result_list['Default'];
        }
      }

      $data->$field = $data;

    }


    // Create Model Instance
    $model_instance = new self::$_database_model(
      [
        'name'  => self::$_database_name,
        'table' => self::$_database_table,
      ],
      $data
    );


    return $model_instance;

  }



  #
  # Load Model Instance
  #
  public static function load($kwargs = []) {

    if (empty(self::$_database_table) == true) {
      self::throw_exception('->load() self::$_database_table is not set');
    }
    if (empty(self::$_database_model) == true) {
      self::throw_exception('->load() self::$_database_model is not set');
    }

    $kwargs = default_empty_array($kwargs, [
      'id'              => null,
      'where'           => null,
      'include_related' => false,
    ]);


    $query = '
      SELECT * FROM `' . self::$_database_table . '`
      WHERE id = ' . (int) $kwargs['id']
    ;

    if ($kwargs['where'] === null) {
      $query .= " AND " . print_r($kwargs['where'], true);
    }

    $result                  = self::query($query);
    $result_list             = mysqli_fetch_assoc($result);
    $data                    = new stdClass();
    $related_model_instances = new stdClass();

    foreach ($result_list as $key => $value) {

      $data->$key = self::escape($value, true);

      // Build Related Instances
      if ($include_related === true) {

        // If there is a related table load it. Avoid loading same child id as parent id
        if (!strstr($key, '_id') === false && $value > 0 && $data->id !== $value) {

          // Relation columns should be named as table name with _id suffix
          $related_table = strstr($key, '_id', true);
          $related_key   = snake_to_pascal_case($related_table);

          $related_model_instances->$related_key = self::load($related_table, $value, $include_related);

        }
      }

    }


    // Create Model Instance
    $model_instance = new self::$_database_model(
      [
        'name'  => self::$_database_name,
        'table' => self::$_database_table,
      ],
      $data,
      $related_model_instances
    );

    return $model_instance;

  }



  #
  # Load All Model Instances
  # @param arr $id_list Array of ids to load
  # @param str $where
  # @note Relations are not allowed here to prevent bad habits and overloading queries.
  #
  public static function loadAll($kwargs = []) {

    if (empty(self::$_database_table) == true) {
      self::throw_exception('->load() self::$_database_table is not set');
    }
    if (empty(self::$_database_model) == true) {
      self::throw_exception('->load() self::$_database_model is not set');
    }


    $kwargs = default_empty_array($kwargs, [
      'id_list' => null,
      'where'   => null,
    ]);

    $query = '
      SELECT * FROM `' . self::$_database_table . '`
      WHERE 1=1 '
    ;

    if ($kwargs['where'] === null) {
      $query .= " AND " . print_r($kwargs['where'], true);
    }
    if (is_array($kwargs['id_list']) == true) {
      $query .= ' AND `id` IN(' . self::escape(implode(',', $kwargs['id_list'])) . ')';
    }


    $result     = self::query($query);
    $model_list = array();
    $data       = new stdClass();

    while($result_list = mysqli_fetch_assoc($result)) {

      foreach ($result_list as $key => $value) {
        $data->$key = self::escape($value, true);
      }

      // Create Model Instance on list
      $model_list[$data->id] = new self::$_database_model(
        [
          'name'  => self::$_database_name,
          'table' => self::$_database_table,
        ],
        $data
      );

    }

    return $model_list;

  }



  #
  # Store model in table
  # @param ApurnaModel $model_instance
  #
  public static function store($model_instance = null) {

    if (is_subclass_of($model_instance) !== 'ApurnaModel') {
      self::throw_exception('->store() Model Instance is not a ApurnaModel');
    }

    $original_id = $model_instance->getID();
    $table       = $model_instance->getTable();

    if (empty($table) == true) {
      return false;
    }

    // Convert BaseModel to array
    $array = get_object_vars($model_instance);

    // Get field types and format data types correctly
    $result = self::query('DESCRIBE `' . $table . '`');
    while ($result_list = mysqli_fetch_assoc($result)) {

      $data    = $array[$result_list['Field']];
      $type    = $result_list['Type'];
      $null    = $result_list['Null'];
      $default = $result_list['Default'];

      if (strstr($type, 'int')) {
        $data = (int) $data;
      } elseif (strstr($type, 'double')) {
        $data = (float) $data;
      } elseif (strstr($type, 'decimal')) {
        $data = (float) $data;
      } elseif (strstr($type, 'float')) {
        $data = (float) $data;
      } elseif (strstr($type, 'char')) {
        $data = self::escape($data);
      } elseif (strstr($type, 'enum')) {
        $data = self::escape($data);
      } elseif (strstr($type, 'text')) {
        $data = self::escape($data);
      } elseif (strstr($type, 'blob')) {
        $data = self::escape($data);
      } elseif (strstr($type, 'year')) {
        $data = (int) $data;
      } elseif (strstr($type, 'date')) {
        if (empty($data) == false) {
          $data = date("Y-m-d", strtotime($data));
        } else {
          $data = null;
        }
      } elseif (strstr($type, 'time')) {
        if (empty($data) == false) {
          $data = date("H:i:s", strtotime($data));
        } else {
          $data = null;
        }
      } elseif (strstr($type, 'datetime')) {
        if (empty($data) == false) {
          $data = date("Y-m-d H:i:s", strtotime($data));
        } else {
          $data = null;
        }
      } elseif (strstr($type, 'timestamp')) {
        if (empty($data) == false) {
          $data = date("Y-m-d H:i:s", strtotime($data));
        } else {
          $data = null;
        }
      }

      // Set blank data to default values
      if (empty($data) == true) {
        if ($default == null && $null == 'YES') {
          $data = null;
        }
        elseif ($default != '' && $default !== null) {
          $data = $default;
        }
      }

      $array[$result_list['Field']] = $data;

    }

    $new_id = false;
    if ($original_id > 0) {
      $type = 'UPDATE';
      $where = 'id = ' . $original_id;
      if ($original_id == $array['id']) {
        // If ID is the same then remove from update
        unset($array['id']);
      } else {
        $new_id = true;
      }
    } else {
      $type   = 'INSERT';
      $where  = NULL;
      $new_id = true;
    }

    // Put data to the db, grab new inserted id
    $id = self::_put($type, $table, $array, $where);

    // If this isn't a new id, then use original id
    if ($new_id == false) {
      $id = $original_id;
    }

    return $id;

  }



  #
  # Archive
  # @param ApurnaModel $model_instance
  #
  public static function archive($model_instance = null) {

    if (is_subclass_of($model_instance) !== 'ApurnaModel') {
      self::throw_exception('->archive() Model Instance is not a ApurnaModel');
    }

    $original_id = $model_instance->getID();
    $table       = $model_instance->getTable();

    if ($result = self::query('UPDATE `' . $table . '` SET `archived_at` = NOW() WHERE id = ' . $original_id)) {
      return $original_id;
    }
    else {
      return false;
    }

  }



  #
  # Discard (Soft Delete)
  # @param ApurnaModel $model_instance
  #
  public static function discard($model_instance = null) {

    if (is_subclass_of($model_instance) !== 'ApurnaModel') {
      self::throw_exception('->discard() Model Instance is not a ApurnaModel');
    }

    $original_id = $model_instance->getID();
    $table       = $model_instance->getTable();

    if ($result = self::query('UPDATE `' . $table . '` SET `discarded_at` = NOW() WHERE id = ' . $original_id)) {
      return $original_id;
    }
    else {
      return false;
    }

  }



  #
  # Delete from database
  # @param ApurnaModel $model_instance
  #
  public static function delete($model_instance = null) {

    if (is_subclass_of($model_instance) !== 'ApurnaModel') {
      self::throw_exception('->delete() Model Instance is not a ApurnaModel');
    }

    $original_id = $model_instance->getID();
    $table       = $model_instance->getTable();

    if ($result = self::query('DELETE FROM `' . $table . '` WHERE id = ' . $original_id)) {
      return $original_id;
    }
    else {
      return false;
    }

  }



  #
  # Get All Lines
  # @param str $query
  # @param boolean $strip_escape
  #
  public static function getAll($query, $strip_escape = true) {

    $result = self::query($query);
    while ($result_list = mysqli_fetch_assoc($result)) {
      if ($strip_escape == true) {
        $return[] = self::escape($result_list);
      } else {
        $return[] = $result_list;
      }
    }

    return $return;

  }



  #
  # Get One Row
  # @param str $query
  # @param boolean $stripescapestrip_escape
  #
  public static function getRow($query, $strip_escape = true) {

    $result = self::query($query . ' LIMIT 1');
    if (($result_list = mysqli_fetch_assoc($result))) {
      if ($strip_escape) {
        return self::escape($result_list);
      } else {
        return $result_list;
      }
    }

  }



  #
  # Get One Cell
  # @param str $query
  #
  public static function getCell($query) {

    $result = self::query($query . ' LIMIT 1');
    if (($result_list = mysqli_fetch_assoc($result))) {
      $key = key($result_list);
      return $result_list[$key];
    }

  }



  #
  # Fetch Object
  # @param str $query
  #
  public static function fetch($query) {

    $result = self::query($query);
    if (($return = mysqli_fetch_object($result))) {
      return $return;
    }

  }



  #
  # Fetch Column Max
  # @param str $column
  # @param str $table
  #
  public static function max($column = '*', $table = null) {

    if (empty($table) == true) {
      $table = self::$_database_table;
    }
    if (empty($table) == true) {
      self::throw_exception('->dispense() self::$_database_table is not set');
    }

    $result = self::query('SELECT MAX(' . $column . ') AS value FROM `' . $table . '`');
    if (($return = mysqli_fetch_assoc($result))) {
      return $return['value'];
    }

  }



  #
  # Fetch Column Min
  # @param str $column
  # @param str $table
  #
  public static function min($column = '*', $table = null) {

    if (empty($table) == true) {
      $table = self::$_database_table;
    }
    if (empty($table) == true) {
      self::throw_exception('->dispense() self::$_database_table is not set');
    }

    $result = self::query('SELECT MIN(' . $column . ') AS value FROM `' . $table . '`');
    if (($return = mysqli_fetch_assoc($result))) {
      return $return['value'];
    }

  }



  #
  # Fetch Row Count
  # @param str $column
  # @param str $where
  # @param str $table
  #
  public static function count($column = '*', $where = null, $table = null) {

    if (empty($table) == true) {
      $table = self::$_database_table;
    }
    if (empty($table) == true) {
      self::throw_exception('->dispense() self::$_database_table is not set');
    }

    $where  = ($where === null ? '' : ' WHERE ' . $where); // Add where clause if passed
    $result = self::query('SELECT COUNT(' . $column . ') AS value FROM `' . $table . '`' . $where);
    if (($return = mysqli_fetch_assoc($result))) {
      return $return['value'];
    }

  }



  #
  # Put data with an array
  # @param str $table
  # @param arr $data
  # @param str $where
  #
  protected static function _put($type, $table, $data = [], $where = NULL) {

    if ($type == 'INSERT' || $type == 'UPDATE') {

      reset($data);

      foreach ($data as $key => $val) {
        $set_data[] = "`$key` = " . ($val === NULL ? 'NULL' : "'$val'");
      }

      $set_data = join(', ', $set_data);
      $where    = ($where === NULL ? "" : "WHERE $where");
      $type     = ($type == 'INSERT' ? 'INSERT INTO' : $type);
      $result   = self::query("$type `$table` SET $set_data $where");

      return mysqli_insert_id(static::$_database_connection);

    }
    else {
      return false;
    }

  }



  #
  # Add escape character for database entry
  # @param str|arr|obj $variable Can accept string, array or object
  # @param bln $strip Strip slashes. Default is to add slashes
  #
  public static function escape($variable, $strip = false) {

    // Escape Array
    if (is_array($variable) == true) {
      foreach ($variable as $key => $value) {
        $variable[$key] = ($strip ? stripslashes($value) : addslashes($value));
      }
      return $variable;
    }

    // Escape Object
    elseif (is_object($variable) == true) {
      foreach ($variable as $key => $value) {
        $variable->$key = ($strip ? stripslashes($value) : addslashes($value));
      }
      return $variable;
    }

    // Escape String
    else {
      $variable = ($strip ? stripslashes($variable) : addslashes($variable));
      return $variable;
    }

  }



  #
  # Throw Exception
  #
  public static function throw_exception($message) {

    include_once VENDOR_ROOT . '/apurna/exception/ApurnaDatabaseOrmException.php';

    $message = get_called_class(self) . print_r($message, true);

    throw new ApurnaDatabaseOrmException($message);

  }






}


