<?php

//  Load Request
switch ($params[1]) {
  case "movies":
    include APPDIR . "/controller/netflix.movies.controller.php";
    break;

  case "tv":
    include APPDIR . "/controller/netflix.tv.controller.php";
    break;

  case "genres":
    include APPDIR . "/controller/netflix.genres.controller.php";
    break;

  case "search":
    include APPDIR . "/controller/netflix.search.controller.php";
    break;

  case "random":
    include APPDIR . "/controller/netflix.random.controller.php";
    break;

  default:
    header($_SERVER["SERVER_PROTOCOL"]." 400 Bad Request");
    $json->status->code = 400;
    $json->status->message = "Bad Request";
    exit;
}