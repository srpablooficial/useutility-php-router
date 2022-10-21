<?php

use useutility\php\router\Router;

require_once __DIR__ . "/vendor/autoload.php";

$Router = new Router(true);

$Router->router('/users', ['methods' => ['GET', 'POST']]);

$Router->router('/users/{name}');

$Router->router('/users/{name}/profile/{id}');

$Router->router('/users/{name}');

//$Router->router('/updates', ['path' => 'carpeta1/nombre', 'methods' => ['POST', 'GET', 'PUT']]);

$Router->noFound(function () {
    echo "que pasa men";
});

$Router->run("/api", "application/json");