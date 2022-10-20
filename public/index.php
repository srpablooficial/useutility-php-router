<?php
require_once __DIR__ . "/vendor/autoload.php";

use Api\Configuration\Router\Router;

$Router = new Router(true);

$Router->router('/users', ['path' => 'carpeta1/nombre']);
$Router->router('/users/{id}', ['methods' => ['POST']]);

//$Router->router('/updates', ['path' => 'carpeta1/nombre', 'methods' => ['POST', 'GET', 'PUT']]);

$Router->noFound(["error" => true, "message" => "No data", "data" => null, "token" => null]);

$Router->run("/api");