<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 18/02/2020
 * Time: 21:41
 */

namespace Plexus;


use Plexus\DataType\Collection;
use Plexus\Error\ConfigError;
use Plexus\Error\PlexusError;
use Plexus\Exception\HaltException;
use Plexus\Exception\PlexusException;
use Plexus\Service\AbstractService;
use Plexus\Service\DatabaseManager;
use Plexus\Service\Router;
use Plexus\Utils\Logger;
use Plexus\Utils\Path;
use Plexus\Utils\Text;

class Application extends AbstractRuntime {

    /**
     * @var string
     */
    public $root_path;

    /**
     * @var Collection
     */
    protected $services;

    /**
     * @var Collection
     */
    protected $modules;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Collection
     */
    protected $globalConfigurations;

    /**
     * @var Collection
     */
    protected $configurations;

    /**
     * Application constructor.
     * @param $root_path
     */
    public function __construct($root_path) {

        try {

            parent::__construct($this);
            $this->root_path = Path::absolute($root_path);

            $this->services = new Collection();
            $this->modules = new Collection();
            $this->globalConfigurations = new Collection();
            $this->configurations = new Collection();

            $this->request = new Request();
            $this->response = new Response();
            $this->session = new Session();

            $this->deploy();

            $this->registerServices();
            $this->registerModules();

        } catch (\Throwable $exception) {

            $this->log($exception);

            if ($this->env('dev')) {
                $this->printStackTrace($exception);
            } else {
                $this->handleHaltException(new HaltException(500));
            }
            exit();
        }

    }

    /**
     * @throws ConfigError
     */
    protected function registerServices() {

    }

    /**
     *
     */
    protected function onRun() {

    }

    /**
     *
     */
    private function _onRun() {
        $this->onRun();
        $this->modules->each(function($name, Module $module) {
            $module->onRun();
        });
        $this->services->each(function($name, AbstractService $service) {
            $service->onRun();
        });
    }

    protected function deploy() {
        $this->_mkdir("config");
        $this->_mkdir("log");

        $this->_mkdir(Path::build("src", "Extensions"));
        $this->_mkdir(Path::build("src", "Modules"));
        $this->_mkdir(Path::build("src", "Services"));
        $this->_mkdir(Path::build("src", "templates"));
    }

    /**
     * @throws \Exception
     */
    protected function registerModules() {
        $modulesDir = Path::build($this->root_path, "src", "Modules");
        if (!is_dir($modulesDir)) {
            mkdir($modulesDir);
        }

        try {
            $classInfo = new \ReflectionClass($this);
        } catch (\Exception $exception) {
            throw new ConfigError();
        }

        $files = scandir($modulesDir);
        if ($files) {
            foreach ($files as $file) {
                if ($file[0] == '.' || !preg_match('/.*Module$/', $file)) {
                    continue;
                }

                $module_name = $file;
                $module_class = sprintf('\\%s\\Modules\\%s\\%s', $classInfo->getNamespaceName(), $module_name, $module_name);
                if (!class_exists($module_class)) {
                    throw new \Exception('Aucune classe nommée "'.$module_class.'" n\'a été trouvée lors du chargement des modules.');
                }
                $this->modules->set($module_name, new $module_class($this));
            }
        }

    }

    /**
     * @param HaltException $haltException
     */
    protected function handleHaltException(HaltException $haltException) {
        $response = $this->getResponse();
        $request = $this->getRequest();

        try {
            if (!$response->isLocked()) {
                $response->setStatusCode($this->httpCodeForHaltCode($haltException->getCode()));


                    // Client looks for a JSON response
                    if (stripos($request->header('Accept'), 'json') !== false) {
                        $response->json([
                            'status' => $haltException->getCode(),
                            'success' => false,
                            'message' => $haltException->getMessage() !== null ? $haltException->getMessage() : $this->messageForHaltCode($haltException->getCode())
                        ]);
                    } else {
                        $body = $this->htmlForHaltCode($haltException->getCode());
                        if ($body !== null) {
                            $response->body($body);
                        }
                    }

            }

            if (!$response->isSent()) {
                $response->send();
            }

        } catch (\Throwable $exception) {
            $this->log($exception);
        }

        exit();
    }

    /**
     * @param $code
     * @return mixed
     */
    public function httpCodeForHaltCode($code) {
        return $code;
    }

    /**
     * @param $code
     * @return string
     */
    public function messageForHaltCode($code) {
        switch ($code) {
            case 401:
                return "Veuillez vous connecter pour accéder à cette ressource.";
            case 403:
                return "Vous n'avez pas le droit d'accéder à cette ressource.";
            case 404:
                return "La ressource que vous avez demandée n'existe pas.";
            case 500:
            default:
                return "Une erreur est survenue lors du traitement de votre requête.";
        }
    }

    /**
     * @param $code
     * @return string
     */
    public function htmlForHaltCode($code) {
        switch ($code) {
            case 200:
                return null;
            case 403:
                return "Forbidden";
            case 404:
                return "Not Found";
            case 500:
            default:
                return Text::format("HTTP Error {}", $code);
        }
    }

    /**
     * @param \Throwable $exception
     */
    protected function printStackTrace(\Throwable $exception) {

        echo "<style>* {font-family: sans-serif;}pre{font-family: monospace; max-width: 100%; overflow-x: scroll}h2{border-bottom: 2px solid #eeeeee} div{max-width: 1250px; margin: auto} div > div {padding-left: 15px; border-left: 2px solid #999999}</style>";
        echo "<h1 style='text-align: center'>Stacktrace</h1>";

        do {
            echo "<div>";
            echo Text::format("<h2>{}</h2>", get_class($exception));
            echo Text::format("<h3>in {}:{}</h3>", str_replace($this->root_path.DIRECTORY_SEPARATOR, "", $exception->getFile()), $exception->getLine());
            if (strlen($exception->getMessage()) > 0) {
                echo Text::format("<p>{}</p>", $exception->getMessage());
            }
            echo Text::format("<pre>{}</pre>", str_replace($this->root_path.DIRECTORY_SEPARATOR, "", $exception->getTraceAsString()));
        } while ($exception = $exception->getPrevious());
    }

    /**
     *
     */
    public function run() {

        $this->_onRun();

        $router = Router::fromRuntime($this);
        try {
            $router->dispatch();
        } catch (HaltException $haltException) {
            $this->handleHaltException($haltException);
        } catch (\Throwable $exception) {

            $this->log($exception);

            if ($this->env('dev')) {
                $this->printStackTrace($exception);
            } else {
                $this->handleHaltException(new HaltException(500));
            }

        }
    }

    /**
     * @param $data
     * @param $type
     */
    public function log($data, $type=null) {
        $logDir = Path::build($this->root_path, "log");
        Logger::log($data, $logDir, $type);
    }

    /**
     * @param $message
     * @param $type
     * @param array $options
     */
    public function flash($message, $type, $options=[]) {
        $this->getSession()->flash($message, $type, $options);
    }

    /**
     * @param $code
     * @param null $message
     * @throws HaltException
     */
    public function halt($code=null, $message=null) {
        $code = $code ? $code : 200;
        throw new HaltException($code, $message);
    }

    /**
     * @param $url
     * @throws HaltException
     */
    public function redirect($url) {
        $this->getResponse()->redirect($url);
        $this->halt(303, "Redirect");
    }

    /**
     * @throws HaltException
     */
    public function refresh() {
        $this->redirect($this->getRequest()->uri());
        $this->halt(205, "Redirect");
    }

    /**
     * @param $route_name
     * @param mixed ...$params
     * @return string
     */
    public function uriFor($route_name, ...$params) {
        $router = Router::fromRuntime($this);
        return $router->uriFor($route_name, ...$params);
    }

    /**
     * @param null $env
     * @return bool|string
     */
    public function env($env=null) {
        $applicationConfiguration = $this->getGlobalConfiguration('application');
        if ($env) {
            return strtolower($env) == strtolower($applicationConfiguration->get('environment', 'prod'));
        } else {
            return strtolower($applicationConfiguration->get('environment', 'prod'));
        }
    }

    /**
     * @return string
     */
    public function base_url() {
        $applicationConfiguration = $this->getGlobalConfiguration('application');
        return strtolower($applicationConfiguration->get('base_url', ''));
    }


    /**
     * @param $name
     * @param bool $override
     * @return Configuration
     */
    public function getGlobalConfiguration($name, $override=false) {
        if (!$this->globalConfigurations->isset($name) || $override) {
            $configFolder = Path::build($this->root_path, "config");
            if (!is_dir($configFolder)) {
                mkdir($configFolder);
            }
            $configPath = Path::build($configFolder, Text::format("{}.ini", $name));
            $this->globalConfigurations->set($name, new Configuration($configPath));
        }
        return $this->globalConfigurations->get($name);

    }

    /**
     * @param $name
     * @param bool $override
     * @return Configuration
     */
    public function getConfiguration($name, $override=false) {
        if (!$this->configurations->isset($name) || $override) {
            $configFolder = Path::build($this->root_path, "src", "config");
            if (!is_dir($configFolder)) {
                mkdir($configFolder);
            }
            $configPath = Path::build($configFolder, Text::format("{}.ini", $name));
            $this->configurations->set($name, new Configuration($configPath));
        }
        return $this->configurations->get($name);

    }

    /**
     * @param $name
     * @return ModelManager
     */
    public function getModelManager($name) {
        $dbManager = DatabaseManager::fromRuntime($this);
        return $dbManager->getModelManager($name);
    }

    /**
     * @param $name
     * @return Module
     */
    public function getModule($name) {
        if (!$this->modules->isset($name)) {
            throw new PlexusError(Text::format("Aucun module nommé '{}' n'a pu être trouvé.", $name));
        }
        return $this->modules->get($name);
    }

    /**
     * @return Request
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * @return Session
     */
    public function getSession() {
        return $this->session;
    }

    /**
     * @return Collection
     */
    public function getServices() {
        return $this->services;
    }

    /**
     * @param $name
     * @return Module
     */
    public function getService($name) {
        if (!$this->modules->isset($name)) {
            throw new PlexusError(Text::format("Aucun service nommé '{}' n'a pu être trouvé.", $name));
        }
        return $this->modules->get($name);
    }

    private function _mkdir($dirname) {
        $dir = Path::build($this->root_path, $dirname);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
    }

}