<?php
/**
 * Controls the primary interactions with the database.
 *
 *
 * Usage
 * Database -> write  ( SQL, DATA )         // UPDATE returns TRUE | FALSE, INSERT returns ROW ID | FALSE
 * Datebase -> select ( SQL, DATA, TABLE )  // returns associated array with correct datatypes if TABLE is provided | FALSE
 * Database -> search ( SQL, DATA )         // returns associated array | FALSE
 * Database -> delete ( SQL, DATA )         // returns number of affected rows | FALSE
 */

class Database {
  private $conn;

  public function __construct($dbselect=null) {
    // Load the database settings from an external file
    $config = parse_ini_file( APPDIR . "/config.ini" );
    if(!empty($dbselect))
      $config["DBNAME"] = $dbselect;
    try {
      $this->conn = new PDO("mysql:host=".$config["DBSERVER"].";dbname=".$config["DBNAME"], $config["DBUSER"], $config["DBPASSWORD"],array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")) or
                    die("There was a problem connecting to the database.");

      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
      error_log("Connection failed: " . $e->getMessage());
    }
  }

  public function write($sql,$data=null) {
    $olddata = (array) $data;
    $data = array();
    foreach($olddata as $v) {
      if(empty($v)) {$data[] = null;} else {$data[] = $v;}
    }
    if(!count($data))
      $data = null;
    try{
      $this->conn->beginTransaction();
        $st = $this->conn->prepare($sql);
        $st->execute($data);
        $id = $this->conn->lastInsertId();
      $this->conn->commit();
      if($id)
        return $id;
      return true;
    } catch (PDOException $e) {
      error_log($e->getMessage());
      $this->conn->rollBack();
      return false;
    }

  }

  public function select($sql,$data=null,$table=null) {
    if(!empty($data))
      $data = array_values((array) $data);
    try{
      $st = $this->conn->prepare($sql);
      $st->execute($data);
      $st->setFetchMode(PDO::FETCH_ASSOC);
      $meta = $this->meta($table);
      $result = array();
      // Manually cast data types as the native driver doesn't yet handle tinyint(1) as boolean values
      while($row = $st->fetch()) {
        if($meta) {
          reset($row);
          $i = count($row);
          while($i--) {
            $field = current($row);
            settype($field, $meta->{key($row)}->type ?: "string");
            $row[key($row)] = $field;
            next($row);
          }
        }
        $result[] = $row;
      }
      return $result;
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return false;
    }
  }

  public function search($sql,$data = null) {
    if(empty($data)||!is_scalar($data))
      return false;
    try{
      $st = $this->conn->prepare($sql);
      for($i=0;$i<substr_count($sql,"?");++$i)
        $st->bindParam($i+1, $data, PDO::PARAM_STR);
      $st->execute();
      $st->setFetchMode(PDO::FETCH_ASSOC);
      return $st->fetchAll();
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return false;
    }
  }

  public function delete($sql,$data = null) {
    if(empty($data))
      return false;
    try{
      $st = $this->conn->prepare($sql);
      if(is_array($data)||is_object($data)) {
        $data = array_values((array) $data);
        $st->execute($data);
      } else {
        for($i=0;$i<substr_count($sql,"?");++$i)
          $st->bindParam($i+1, $data, PDO::PARAM_STR);
        $st->execute();
      }
      return $st->rowCount();
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return false;
    }
  }

  protected function meta($table=null) {
    if(empty($table))
      return false;
    $sql = sprintf("show columns from %s",addslashes($table));
    $st = $this->conn->query($sql);
    $st->setFetchMode(PDO::FETCH_ASSOC);

    $meta = (object) null;
    while($row = $st->fetch()){
      $object =  (object) null;
      $object->type = $this->parseType($row["Type"]);
      $object->primary = $row["Key"]==="PRI";
      $object->table = (string) $table;
      $meta->{$row["Field"]} = $object;
    }
    return $meta;
  }

  protected function parseType($type=null){
    list($type) = explode(" ",$type);
    $type = strstr($type,"(",true) ?: $type;
    $ints = array("int","smallint","mediumint","bigint","year");
    $floats = array("float","double","decimal");
    if($type==="tinyint") {
      return "boolean";
    } else if(in_array($type,$ints)) {
      return "integer";
    } else if(in_array($type,$floats)) {
      return "float";
    }
    return "string";
  }

  public function __destruct() {
    //disconnect from database
  }
}