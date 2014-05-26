<?php

require_once APPDIR . "/classes/api.class.php";
$api = new API($dbselect);

function cleanIMDB($str){
  $str = substr($str, 2);
  return ltrim($str,"0");
}


// List all items
if( !isset($params[2]) ) {
  $terms = array("tv"=>true);
  $results = $api->movies->find($terms);
  if($results) {
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
                  "tv"    => true,
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
    $json->episodes = $api->episodes->getmovieid($params[2]);
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