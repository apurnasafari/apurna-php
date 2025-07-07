<?php


#
# Apurna Logic
#



#
# Is Empty
#
function is_empty($value = null) {

  if ($value == null || $value == '' || $value == 0) {
    return true;
  }

  return false;

}



#
# Is Set
#
function is_set($value = null) {

  if ($value == null || $value == '' || $value == 0) {
    return false;
  }

  return True;

}



#
# Has Attribute
#
function has_attr($instance = null, $attribute = null) {

  return null;

}



#
# Get Attribute
#
function get_attr($instance = null, $attribute = null, $default = null) {

  return null;

}



#
# Set Attribute
#
function set_attr($instance = null, $attribute = null, $default = null) {

  return null;

}



#
# Slugify String
#
function slugify($value = null) {

  if (is_empty($value) == True) {
    return '';
  }

  // @wip Convert to PHP
  // $value = parse_str(value)
  // $value = value.lower().strip()
  // $value = value.replace('/', '-').replace('\\', '-')
  // $value = re.sub(r'[^\w\s-]', '',  value)
  // $value = re.sub(r'[\s_-]+',  '-', value)
  // $value = re.sub(r'^-+|-+$',  '',  value)

  return null;

}



#
# Default Empty
#
function default_empty($value = null, $default_value = null) {

  if (is_empty($value) == true) {
    return $default_value;
  }

  return $value;

}



#
# @wip Default Empty Array
#
function default_empty_array($array = null, $default_array = null, $default_value = []) {

  // Initialize array if not already
  $array         = default_empty($array,         $default_value);
  $default_array = default_empty($default_array, $default_value);


  // Loop through default values and add if json is empty
  foreach($default_array as $key => $value) {

    if ($value !== null) {
      if(is_array($value) == true) {
        $array[$key] = default_empty_array($array[$key], $value, []);
      }
    }
    else if(array_key_exists($key, $array) == true) { // Key is set
      if(is_empty($array[$key]) == true) {
        $array[$key] = $value;
      }
    }
    else { // Key not set on array
      $array[$key] = $value;
    }

  };

  return $array;

}



#
# @wip Calc Float
#
function calc_float($value_a = null, $operation = null, $value_b = null) {

  return null;

}



#
# Snake to Pascal Case
# @example example_object > ExampleObject
#
function snake_to_pascal_case($value = '') {

  $value   = str_replace('_', ' ', $value);
  $value   = ucwords('_', ' ', $value);
  $value   = str_replace('',  '',  $value);

  return $value;

}


