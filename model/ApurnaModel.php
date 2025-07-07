<?php


#
# Apurna Model Class
#
class ApurnaModel {

  protected static $_database_id    = null;
  protected static $_database_name  = null;
  protected static $_database_table = null;



  /**
   * Class Constructor
   * @param array  $database
   * @param object $data
   * @param array $related_model_instances
   */
  public function __construct($database = [], $data, $related_model_instances = null) {

    $this->_database_name  = $database['name'];
    $this->_database_table = $database['table'];
    $this->_database_id    = $data->id;

    // Set data
    foreach($data as $key => $value) {
      $this->$key = $value;
    }

    // Tack on related model instances list
    if(is_object($related_model_instances) == true) {
      foreach($related_model_instances as $key => $value) {
        $this->$key = $value;
      }
    }

  }



  /**
   * Get Original Database ID
   */
  public function getID() {

    return $this->_database_id;

  }



  /**
   * Get Database Name
   */
  public function getDatabaseName() {

    return $this->_database_name;

  }



  /**
   * Get Database Table
   */
  public function getDatabaseTable() {

    return $this->_database_table;

  }



  /**
   * Save Element
   */
  public function save() {

    return DB::store($this);

  }



}



