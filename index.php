<?php

use useutility\php\router\Router;

require_once __DIR__ . "/vendor/autoload.php";

$Router = new Router(true);

$Router->router('/users/{name}/profile');
$Router->router('/users/{name}/profile/{id}');

$Router->router('/users/{name}');
$Router->router('/users');

//$Router->router('/updates', ['path' => 'carpeta1/nombre', 'methods' => ['POST', 'GET', 'PUT']]);

$Router->noFound(["error" => true, "message" => "No data", "data" => null, "token" => null]);

$Router->run("/api");