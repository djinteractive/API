<?php
// Consider moving this into the movies subsection.
require_once APPDIR . "/classes/api.class.php";
$api = new API($dbselect);

// Could not find the item
header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
$json->status->code = 404;
$json->status->message =  "Not Found";


$result = $api->movies->random();
if( $result ) {
  header($_SERVER["SERVER_PROTOCOL"]." 200 OK");
  $json->status->code = 200;
  $json->status->message = "OK";
  foreach(reset($result) as $k=>$v)
    $json->$k = $v;
}
exit;