<?php

namespace useutility\php\router;

use Exception;

//require_once $_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php";
class Router
{
    protected $routes = [];
    protected $noFound;
    protected $routeURL = '';
    protected $modDevelopment = false;

    public function __construct($modDevelopment = false)
    {
        $this->modDevelopment = $modDevelopment;

        //Si esta el modo Development mostraremos los errores de PHP
        if ($this->modDevelopment) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }

        //Creamos Router para htaccess
        $this->create_htaccess();

        //$this->create_json();

    }

    public function router(string $path, $settings = null, $callback = null)
    {
        if (is_callable($settings) && !is_array($settings)) {
            $this->routes[$path]['callback'] = $settings;
        } else {
            $this->routes[$path]['settings'] = $settings;
            $this->routes[$path]['callback'] = $callback;
        }

    }

    public function routerGroup(object $class, $callback = null)
    {
        $callback($this, $class);
    }

    public function noFound($callback = null)
    {
        $this->noFound = $callback;
    }

    public function routeURL($routeURL)
    {
        $this->routeURL = $routeURL;
    }

    private function create_json()
    {

        $pathJson = __DIR__ . "/registerRouter.json";

        if (!file_exists($pathJson)) {
            file_put_contents($pathJson, "{router:[{}]}");
        }

    }

    public function create_extras()
    {
        $file = __DIR__ . "/Files/Handlers.php";

        $path_extras_file = __DIR__ . "/../../Controllers/Commands/Handlers.php";

        $path_extras = __DIR__ . "/../../Controllers/Commands";

        //Si no existe la carpeta se creará *controlller
        if (!file_exists($path_extras)) {
            mkdir($path_extras, 0777, true);
        }

        //Si no existe el archivo de las Clases creamos copia
        if (!file_exists($path_extras_file)) {
            $newFile = file_get_contents($file);
            file_put_contents($path_extras_file, $newFile);

        }
    }

    private function json_update($json)
    {
        $pathJson = __DIR__ . "/registerRouter.json";

        if (file_exists($pathJson)) {

            $getJson = json_decode(file_get_contents($pathJson));

            $change = false;
            foreach ($getJson->router as $key => $value) {

                if (!isset($value->{$json->route})) {
                    $change = true;
                    $value->{$json->route} = $json;

                } else {
                    if ($value->{$json->route} != $json) {
                        $change = true;

                        $value->{$json->route} = $json;
                    }

                }
            }
            file_put_contents($pathJson, json_encode($getJson));

        }

        return $change ? true : false;

    }

    private function create_htaccess()
    {
        $path_htaccess = __DIR__ . "/Files/.htaccess";
        $file = $_SERVER['DOCUMENT_ROOT'] . "/.htaccess";

        //Si no existe el archivo *htaccess se creará
        if (!file_exists($file)) {

            $newFile = file_get_contents($path_htaccess);
            file_put_contents($file, $newFile);
        }
    }

    //Crea las rutas en JSON
    public function create_controller($name, $folder_path)
    {
        $pathBase = __DIR__ . "/Files/create_base.php";

        //Si no existe el archivo de las Clases creamos copia de TEMPLATE en HANDLER

        if (!file_exists("$folder_path/$name.php")) {

            $newFile = file_get_contents($pathBase);

            $changes = str_replace("examples", $name, $newFile);

            file_put_contents("$folder_path/$name.php", $changes);
        }
    }

    //Crea las rutas en JSON
    public function create_path($folder_path)
    {
        //Si no existe la carpeta se creará *controlller
        if (!file_exists($folder_path)) {
            mkdir($folder_path, 0777, true);
        }
    }
    /**
     * Ejecuta la lectura de las funciones impuestas anteriormente, como Routers, estado de desarrollo, path, etc.
     * @param string $routeURL : string path principal de donde se hará la llamda
     */
    public function run(string $routeURL = '', string $contentType = null)
    {
        try {

            $query = [];

            if ($contentType) {
                header("Content-Type: $contentType");
            } else {
                header("Content-Type: application/json");
            }

            #Si la RutaURL está especificada, usar como path principal
            $this->routeURL = $routeURL ? $routeURL : $this->routeURL;

            #Obtenemos el path completo solicitado
            $requestPath = parse_url($_SERVER['REQUEST_URI'])['path'];

            //Si tiene un Path creado, sacamos el primer parametro insertado
            if ($this->routeURL) {
                #Filtramos los primeros string con slash
                if (preg_match("/(^\/\w+)(.+)/", $requestPath, $requestPath_matched)) {
                    #Si el primer parametro del path  es igual al router impuesto routeURL
                    if ($requestPath_matched[1] === $this->routeURL) {
                        $requestPath = $requestPath_matched[2];
                    } else {
                        throw new Exception('no route');
                    }
                }
            }
            //switch para craer accion
            $found = false;

            //Si NO tiene ruta
            if (!$this->routes) {
                return $this->callNoFound();
            }

            //tomamos todos los router Registrados
            foreach ($this->routes as $path => $values) {

                $path = "/" . $path;

                $folder_class = null;
                $name_class = null;

                $callback = isset($values['callback']) ? $values['callback'] : null;
                $settings = isset($values['settings']) ? $values['settings'] : null;

                $METHODS = null;

                //Si obtenemos url con mas de 1 parametro, los unimos con _
                if (preg_match_all("/\/(\w+)/", $path, $path_matched)) {
                    $name_class = ucfirst(implode('_', $path_matched[1]));

                }
                //Si $values tiene meta datos
                if (isset($settings) && is_array($settings)) {

                    if (isset($settings["path"])) {

                        if (preg_match("/(.*)\/(.*)/i", $settings["path"], $settings_path_matched)) {

                            $name_class = ucfirst(str_replace("@controller", '', strtolower($settings_path_matched[2])));

                            $folder_class = "/" . $settings_path_matched[1];
                        } else {

                            $name_class = ucfirst(str_replace("@controller", '', strtolower($settings["path"])));
                        }
                    }
                    if (isset($settings["methods"])) {
                        if (!is_array($settings["methods"])) {
                            throw new Exception("Error Method");
                        }

                        $METHODS = $settings["methods"];
                    }
                }

                $folder_path = $_SERVER['DOCUMENT_ROOT'] . "/Controllers$folder_class/";
                $controller_path = "$folder_path$name_class.php";
                //      if (!file_exists($extras_path)) {  $Template->create_extras(["return" => $this->noFound]);   }

                $this->create_path($folder_path);

                if (!isset($callback)) {
                    $this->create_controller($name_class, $folder_path);

                }

                //    $this->json_update($json);
                $json =
                (object) [
                    "class_name" => $name_class,
                    "route" => $path,
                    "folder" => $folder_class,
                    "methods" => $METHODS,
                    "callback" => $callback ? true : false,
                ];

                if (preg_match_all("/\/(\w+)\/{(\w+)}/i", $path, $path_match) && preg_match_all("/\/(\w+)\/([\w%]+)/i", $requestPath, $requestpath_match)) {

                    $query_keys = $path_match[2];
                    $query_values = $requestpath_match[2];

                    $maxNumb = count($query_keys);

                    if ($maxNumb == count($query_values) && $maxNumb == count($path_match[1])) {

                        $query_values = array_map(function ($values) {
                            return urldecode($values);
                        }, $query_values);

                        $query = array_combine($query_keys, $query_values);

                        $_GET = array_merge($_GET, $query);

                        $url = array_map(function ($key, $values) {
                            return $key . "/" . $values;
                        }, $path_match[1], $query_values);

                        preg_match_all("/{\w+}/i", $path, $path_old);

                        $path = str_replace($path_old[0], $query, $path);

                    }
                }

                //   print_r(PHP_EOL . $this->routeURL . $path . " !== " . $this->routeURL . urldecode($requestPath) . PHP_EOL);

                if ($this->routeURL . $path !== $this->routeURL . urldecode($requestPath)) {
                    continue;
                }
                $found = true;

                if (!$callback) {
                    require $controller_path;
                    $callback = new $name_class();

                    http_response_code(200);

                    $method = $_SERVER['REQUEST_METHOD'];

                    if (method_exists($callback, $method)) {

                        $call_callback = false;

                        if ($METHODS) {
                            foreach ($METHODS as $key => $value) {
                                if ($value === $method) {
                                    $call_callback = true;
                                }
                            }
                        } else {
                            $call_callback = true;

                        }

                        if ($call_callback) {
                            $_POST = $method === 'POST' || $method === 'PUT' ? json_decode(file_get_contents('php://input'), true) : null;

                            if ($callback->{$method}($query) === false) {
                                $this->callNoFound();
                            }
                        } else {
                            throw new Exception('no methods');

                        }
                    }

                } else {
                    $callback();
                }

            }

            #Si al final del foreach no enonctró nada, lanzamos callnoFound()
            if (!$found) {
                return $this->callNoFound();
            }
            return true;

        } catch (\Exception$th) {
            http_response_code(404);
            return $this->callNoFound();
        }
    }

    private function callNoFound()
    {
        if (isset($this->noFound)) {
            if (is_array($this->noFound)) {
                echo json_encode($this->noFound);
            } else {
                $this->noFound();
            }

        }
        return false;

    }

}