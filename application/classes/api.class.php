<?php
/**
 * Primary API for accessing the Database
 *
 * Usage
 * API -> TABLE -> get FIELD ( VALUE )      // returns associated array with correct datatypes | FALSE
 * API -> TABLE -> set FIELD ( VALUE )      // Updates the current state, use to store values before updating the database, returns TRUE
 * API -> TABLE -> save ()                  // INSERT the current state into tohe database, UPDATE on Duplicate Key, returns ROW ID | TRUE | FALSE
 * API -> TABLE -> find ( FIELD => VALUE )  // returns associated array with correct datatypes | FALSE
 * API -> TABLE -> remove ( ID )            // returns number of affected rows | FALSE
 *
 * Requires PHP5.3
 */
require_once APPDIR . "/classes/database.class.php";


class API {
  private $database;
  private $response;


  public function __construct($dbselect=null) {
    $this->database = new Database($dbselect);
    $this->load();
  }

  private function load() {
    $sql = "show tables";
    $result = $this->database->select($sql);
    if($result) {
      foreach ($result as $row) {
        $this->{reset($row)} = new Table($this->database,reset($row));
      }
      return true;
    }
    return false;
  }
}


/**
 * Generic table wrapper
 * Provide consistant functionality between tables
 * Handle retrieval, creating and updating of data
 */
class Table {
  private $database;
  private $table;
  private $primary;
  private $fields = array();
  private $state;

  public function __construct($connection,$table) {
    $this->database = $connection;
    $this->table = $table;
    $this->state = (object) null;
    $this->load();
  }

  // Add closure support to the class
  public function __call($method, $args) {
    if ( $this->{$method} instanceof Closure ) {
      return call_user_func_array($this->{$method},$args);
    } else {
      error_log("Error - Function method does not exist");//return parent::__call($method, $args);
    }
  }

  private function load() {
    $that = &$this; // Create a reference to $this so that we can call it within closures and not have php5.3 freak out
    $sql = sprintf("show columns from %s",$this->table);
    $result = $this->database->select($sql);
    if($result) {
      // Dynamically build functions
      foreach ($result as $row) {
        $field = $row["Field"];
        if($row["Key"]==="PRI")
          $this->primary = $field;
        $this->fields[] = $field;
        // Set
        $this->{"set" . $field} = function ($value) use ($that,$field) {
          return $that->set($field,$value);
        };
        // Get
        $this->{"get" . $field} = function ($value) use ($that,$field) {
          return $that->get($field,$value);
        };
      }
      return true;
    }
    return false;
  }

  public function set($field,$value) {
    $this->state->$field = $value;
    return true;
  }

  public function get($field,$value) {
    $sql = sprintf("select * from %s where %s = ?",$this->table,$field);
    $result = $this->database->select($sql,$value,$this->table);
    if($result)
      return $result;
    return false;

  }

  public function random() {
    $sql = sprintf("select * from %s where tv is not true order by rand() limit 1",$this->table);
    $result = $this->database->select($sql,null,$this->table);
    if($result)
      return $result;
    return false;

  }

  public function find($arr,$matchall=false) {
    $sql = sprintf("select * from %s where ",$this->table);
    $i = 0;
    $seperator = ($matchall) ? " or " : " and " ;
    foreach($arr as $k=>$v) {
      if($i++)
        $sql .= $seperator;
      if($v===null) {
        $sql .= sprintf('%s is null',$k);
        unset($arr[$k]);
      } else if($v===true) {
        $sql .= sprintf('%s is true',$k);
        unset($arr[$k]);
      } else if($v===false) {
        $sql .= sprintf('%s is false',$k);
        unset($arr[$k]);
      } else if(substr($v,0,9)==="INTERVAL:") {
        $sql .= sprintf('%s > date_add(curdate(), interval -%s)',$k,substr($v,9));
        unset($arr[$k]);
      } else
        $sql .= sprintf('concat(" ",%s," ")',$k).' like concat("%",?,"%")';
    }
    $result = $this->database->select($sql,$arr,$this->table);
    if($result)
      return $result;
    return false;
  }

  public function remove($id) {
    $sql = sprintf("delete from %s where %s = ?",$this->table,$this->primary);
    $result = $this->database->delete($sql,$id);
    if($result)
        return $result;
      return false;
  }

  public function save() {
    $sql = sprintf("insert into %s values(",$this->table); //Table
    $sql .= implode(",", array_fill(1,count($this->fields),"?"));
    $sql .= ") on duplicate key update "; // Fallback to update row if exists
    $i = 0;
    foreach((array) $this->state as $k=>$v) {
      if($i)
        $sql .= " , ";
      $sql .= sprintf("%s = values(%s)",$k,$k);
      $i++;
    }

    $data = array();
    foreach ($this->fields as $field) { // Consider merging the 2 foreach statments *****
      // locate field in $this->state and return value else null
      $data[] = (array_key_exists($field,(array) $this->state)) ? $this->state->$field : null;
    }

    $result = $this->database->write($sql,$data);
    if($result) {
      $this->clearState();
      return $result;
    }
    return false;
  }

  public function getState() {
    return $this->state;
  }

  public function clearState() {
    $this->state = (object) null;
  }
}