<?php

require_once APPDIR . "/classes/api.class.php";
$api = new API($dbselect);


// Check for a movie
if( !isset($params[2]) ) {
  header($_SERVER["SERVER_PROTOCOL"]." 406 Not Acceptable");
  $json->status->code = 406;
  $json->status->message =  "Not Acceptable";
  exit;
}


// Could not find the contract
header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
$json->status->code = 404;
$json->status->message =  "Not Found";


// Search by name
$terms = array(
                "name"    => urldecode($params[2]),
                "year"    => urldecode($params[2]),
                "studios" => urldecode($params[2])
              );
$results = $api->movies->find($terms,true);
if( $results ) {
  header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
  $json->status->code = 200;
  $json->status->message = "OK";
  $json->total = count($results);
  $json->results = $results;
}
exit;