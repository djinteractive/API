<?php

require_once APPDIR . "/classes/api.class.php";
$api = new API($dbselect);

// List all items
if( !isset($params[2]) ) {
  $terms = array( "id" => true );
  $results = $api->genres->find($terms);
  if( $results ) {
    header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
    $json->status->code = 200;
    $json->status->message = "OK";
    $json->total = count($results);
    $json->results = $results;
  }
  exit;
}


// Could not find the item
header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
$json->status->code = 404;
$json->status->message =  "Not Found";


// Retrieve item by ID
if( is_numeric($params[2]) ) {
  $results = $api->genres->getid($params[2]);
  if( $results ) {
    $genre = (object) reset($results);

    $results = $api->genresview->getgenreid($genre->id);
    if($results) {
      header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
      $json->status->code = 200;
      $json->status->message = "OK";
      $json->genre = $genre->genre;
      $json->total = count($results);
      $json->results = $results;
    }
  }
  exit;
}


// Retrive item by name
$results = $api->genres->getgenre(urldecode($params[2]));
if( $results ) {
  header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
  $json->status->code = 200;
  $json->status->message = "OK";
  $json->total = count($results);
  $json->results = $results;
}
exit;