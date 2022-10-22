<?php

use useutility\php\router\Router;

require_once __DIR__ . "/vendor/autoload.php";

$Router = new Router(true);

//login user
$Router->router('users/login', ["path" => "users/login", "methods" => ["POST"]]);
//user  get / post / update / delete
$Router->router('users', ["path" => "users/users"]);
//user filter by id only
$Router->router('users/{id}', ["path" => "users/users", "methods" => ["GET"]]);

$Router->noFound(["message" => "no message"]);

$Router->run("/api");