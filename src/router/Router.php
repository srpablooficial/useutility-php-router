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

    public function router(string $path, $settings = null, $callback = null)
    {
        if (is_callable($settings) && !is_array($settings)) {
            $this->routes[$path]['callback'] = $settings;
        } else {
            $this->routes[$path]['settings'] = $settings;
            $this->routes[$path]['callback'] = $callback;

            if (isset($settings["param"])) {

                $new_settings = $settings;
                $new_settings["param"] = null;

                $this->router($path . "/{" . $settings["param"] . "}", $new_settings);

            }

        }

    }

    public function noFound($callback = null)
    {
        $this->noFound = $callback;
    }

    public function routeURL($routeURL)
    {
        $this->routeURL = $routeURL;
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

            #Si tiene contentType lo agregamos al header
            if ($contentType) {
                header("Content-Type: $contentType");
            } else {
                header("Content-Type: application/json");
            }

            //Si NO tiene ruta
            if (!$this->routes) {
                throw new Exception('no existe ninguna ruta');
            }

            #Si la RutaURL está especificada, usar como path principal
            $routeURL = $routeURL ? $routeURL : $this->routeURL;

            #Obtenemos el path completo solicitado
            $requestPath = $rall = parse_url($_SERVER['REQUEST_URI'])['path'];

            //Si tiene un Path creado, sacamos el primer parametro insertado
            if ($routeURL) {
                #Filtramos los primeros string con slash
                if (!preg_match("/(^\/\w+)(.+)/", $requestPath, $requestPath_matched)) {throw new Exception("#0000: no existe route path", 1);}
                #Si el primer parametro del path  es igual al router impuesto routeURL
                if ($requestPath_matched[1] !== $routeURL) {throw new Exception("#0001: no existe route path", 1);}
                #devolvemos la ruta
                $requestPath = $requestPath_matched[2];

            }

            //switch para craer accion
            $found = false;

            $params = [];

            //tomamos todos los router Registrados

            foreach ($this->routes as $path => $values) {

                $path = "/" . $path;

                $callback = isset($values['callback']) ? $values['callback'] : null;
                $settings = isset($values['settings']) ? $values['settings'] : null;

                $Controllers_folder = $_SERVER['DOCUMENT_ROOT'] . "/Controllers/";

                #Si no existe carpeta de Controllers
                $this->create_path($Controllers_folder);

                $json = (object) [
                    "class_name" => null,
                    "route" => null,
                    "param" => null,
                    "folder_path" => null,
                    "methods" => null,
                    "callback" => null,
                ];

                //Si obtenemos url con mas de 1 parametro, los unimos con _
                if (!preg_match_all("/\/(\w+)/", $path, $path_matched)) {
                    throw new Exception("Error Method");
                }

                #Obtenemos nombre del Path
                $json->class_name = ucfirst(implode('_', $path_matched[1]));

                //Si $values tiene meta datos
                if (isset($settings) && is_array($settings)) {

                    if (isset($settings["src"])) {
                        //$json->class_name = ucfirst(str_replace("@controller", '', strtolower($settings["src"])));

                        $json->folder_path = $settings["src"];

                        /*
                    #Si existe SRC y tiene mas de un slash
                    if (preg_match("/(.*)\/(.*)/i", $settings["src"], $settings_path_matched)) {

                    $json->class_name = ucfirst(str_replace("@controller", '', strtolower($settings_path_matched[2])));

                    $json->folder_path = $settings_path_matched[1];

                    } else {
                    #Si existe SRC y NO tiene mas de un slash
                    $json->class_name = ucfirst(str_replace("@controller", '', strtolower($settings["src"])));
                    }

                     */
                    }

                    if (isset($settings["name"])) {

                        $json->class_name = $settings["name"];
                    }

                    if (isset($settings["methods"])) {
                        if (!is_array($settings["methods"])) {
                            throw new Exception("Error Method");
                        }

                        $json->methods = $settings["methods"];
                    }

                }

                if (!isset($callback)) {
                    $this->create_path($Controllers_folder . $json->folder_path);
                    $this->create_controller($json->class_name, $Controllers_folder . $json->folder_path);
                }

                if (preg_match_all("/\/([\w{}]+)/", $path, $path_match) && preg_match_all("/\/([\w]+)/", $requestPath, $requestpath_match)) {

                    $path_get = $path_match[1];
                    $request_path_get = $requestpath_match[1];

                    if (count($path_get) === count($request_path_get)) {

                        $get_patch = array_map(function ($request, $path) {
                            if ($request === $path) {
                                return $path;
                            } elseif ($request !== $path && preg_match("/{(\w+)}/", $path, $new_key)) {

                                return $new_key[1];
                            }

                        }, $request_path_get, $path_get);

                        $requestPath = "/" . implode('/', $get_patch);

                        $combine = array_combine($path_get, $request_path_get);

                        $combine = array_filter($combine, function ($key) {
                            if (preg_match("/{(\w+)}/", $key)) {
                                return true;
                            }
                        }, ARRAY_FILTER_USE_KEY);

                        $new_combine_keys = array_map(function ($key) {
                            if (preg_match("/{(\w+)}/", $key, $new_combine)) {
                                return $new_combine[1];

                            }}, array_keys($combine));

                        $params = array_combine($new_combine_keys, $combine);

                        $_GET = array_merge($_GET, $params);

                    }

                }

                //print_r(PHP_EOL . $path . " !== " . urldecode($requestPath) . PHP_EOL);

                $path = str_replace(['{', '}'], '', $path);

                if ($path !== $requestPath) {

                    continue;
                }

                $found = true;

                if (!$callback) {

                    // print_r($Controllers_folder . $json->folder_path . "/" . $json->class_name . '.php');

                    require $Controllers_folder . $json->folder_path . "/" . $json->class_name . '.php';
                    $callback = new $json->class_name;

                    http_response_code(200);

                    $method = $_SERVER['REQUEST_METHOD'];

                    if (method_exists($callback, $method)) {

                        $call_callback = false;

                        if ($json->methods) {
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

                            if ($callback->{$method}($params) === false) {
                                throw new Exception('no function');

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
            $this->callNoFound($th->getMessage());
        }
    }

    private function callNoFound($message = 'no message')
    {
        if (isset($this->noFound)) {
            if (is_array($this->noFound)) {

                $this->noFound["message"] = $message;

                echo json_encode($this->noFound);
            } else {
                $this->noFound();
            }

        }
        return false;

    }

}