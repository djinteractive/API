<?php

// Make sure the params have been set
if(empty($params))
  exit;

//  Load Request
switch ($params[0]) {
  case "netflix":
    include APPDIR . "/routes/netflix.route.php";
    break;

  case "nfx": # This will load the most recent data for netflix, though I'm still testing it out.
    $dbselect = "netflix";
    include APPDIR . "/routes/netflix.route.php";
    break;

  default:
    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
    $json->status->code = 400;
    $json->status->message = "400 Bad Request";
    exit;
}