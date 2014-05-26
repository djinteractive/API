<?php
header('Access-Control-Allow-Origin: *');
header( "Content-Type: application/json; charset=utf-8" );

// Turn off error reporting
ini_set("display_startup_errors",false);
ini_set("display_errors",false);
ini_set("html_errors",false);
ini_set("docref_root",false);
ini_set("docref_ext",false);

// Set default values
define( "ROOTDIR", __dir__ );
define( "APPDIR", ROOTDIR . "/application" );
define("REQUEST_URI",strtolower($_SERVER["REQUEST_URI"]));
header($_SERVER["SERVER_PROTOCOL"]." 503 Service Unavailable");
$json = (object) null;
$json->status->code = 503;
$json->status->message =  "Service Unavailable";

// Display JSON on exit.
function display($json) {
  echo json_encode($json);
}
register_shutdown_function('display',$json);
/*
// Echo back request
if($_SERVER["REQUEST_METHOD"]!=="GET") {
  header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
  $json->url = $_SERVER["REQUEST_URI"];
  $REQUEST = json_decode(file_get_contents('php://input'));
  $json->{strtolower($_SERVER["REQUEST_METHOD"])} = $REQUEST;
  exit;
}
*/

// Check request format
if(!in_array($_SERVER["REQUEST_METHOD"], array("GET","POST","PUT","OPTIONS"))) {
  header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed");
  $json->status->code = 405;
  $json->status->message = "Method Not Allowed";
  exit;
}

// Process URI
if(strpos(REQUEST_URI,"?")===false)
  $params = explode("/", REQUEST_URI);
else if(strpos(substr(REQUEST_URI,-2),"?")!==false) //fix for rare edge case when an url ends with a question mark
  $params = explode("/", REQUEST_URI);
else
  $params = explode("/", substr(REQUEST_URI,0,strpos(REQUEST_URI,"?")));
$params = array_values(array_filter($params));


include APPDIR . "/router.php";