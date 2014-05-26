<?php

require_once APPDIR . "/classes/api.class.php";
$api = new API($dbselect);

function cleanIMDB($str){
  $str = substr($str, 2);
  return ltrim($str,"0");
}


// Save item to database
if( $_SERVER["REQUEST_METHOD"] === "PUT" ) {
  // Using IP based authentication for now
  // whilst it does the job I'd like to implement a more secure method at some stage
  $config = parse_ini_file( APPDIR . "/config.ini" );
  if( $_SERVER["REMOTE_ADDR"] !== $config["IP"] ) {
    header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");
    $json->status->code = 403;
    $json->status->message = "Forbidden";
    exit;
  }
  unset($config);


  $REQUEST = (array) json_decode(file_get_contents('php://input'));

  header($_SERVER["SERVER_PROTOCOL"]." 406 Not Acceptable");
  $json->status->code = 406;
  $json->status->message = "Not Acceptable";

  if( is_numeric($params[2]) ) {
    $api->movies->setid( $params[2] );
    foreach( $REQUEST as $k => $v ) {
      if(in_array($k, array("name","genreid","added","lastseen","status")))
        continue;
      $api->movies->{ "set" . strtolower($k) }( $v );
    }
    $result = $api->movies->save();
    if( $result ) {
      header($_SERVER["SERVER_PROTOCOL"]." 202 Accepted");
      $json->status->code = 202;
      $json->status->message = "Accepted";
    }
  }
  exit;
}


// List all items
if( !isset($params[2]) ) {
  $terms = array("tv"=>null);
  $results = $api->movies->find($terms);
  if( $results ) {
    header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
    $json->status->code = 200;
    $json->status->message = "OK";
    $json->total = count($results);
    $json->results = $results;
  }
  exit;
}


// Recently Added
if( $params[2] === "recent" ) {
  $terms = array(
                  "tv"    => null,
                  "added" => "INTERVAL: 10 DAY"
                );
  $results = $api->movies->find($terms);
  header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
  $json->status->code = 200;
  $json->status->message = "OK";
  $json->total = ($results) ? count($results) : 0;
  $json->results = $results;
  exit;
}


// Could not find the item
header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
$json->status->code = 404;
$json->status->message =  "Not Found";


// Retrieve item by ID
if( is_numeric($params[2]) ) {
  $result = $api->movies->getid($params[2]);
  if( $result ) {
    header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
    $json->status->code = 200;
    $json->status->message = "OK";
    foreach(reset($result) as $k=>$v)
      $json->$k = $v;
  }
  exit;
}


// Retrive item by IMDB ID
if( substr($params[2], 0, 2) === "tt" ) {
  $result = $api->movies->getimdb(cleanIMDB($params[2]));
  if( $result ) {
    header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
    $json->status->code = 200;
    $json->status->message = "OK";
    foreach(reset($result) as $k=>$v)
      $json->$k = $v;
  }
  exit;
}


// Invalid request
header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
$json->status->code = 400;
$json->status->message = "Bad Request";
exit;