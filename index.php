<?php

use useutility\php\router\Router;

require_once __DIR__ . "/vendor/autoload.php";

$Router = new Router(true);

//login user
$Router->router('discovery', ["param" => 'id', "src" => 'users/discovery']);

$Router->noFound(["message" => "no message"]);

$Router->run("/api");