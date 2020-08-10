<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 19/02/2020
 * Time: 18:36
 */

namespace Plexus;


use Plexus\DataType\Collection;
use Plexus\Error\ConfigError;
use Plexus\Error\PlexusError;
use Plexus\Service\Renderer\AbstractRenderer;
use Plexus\Service\Router;
use Plexus\Utils\Path;
use Plexus\Utils\RegExp;
use Plexus\Utils\Text;

class Module extends AbstractRuntime {

    protected $name;

    /**
     * @var string
     */
    protected $root_path;

    /**
     * @var Collection
     */
    protected $controlers;

    public function __construct(Application $application) {
        parent::__construct($application);

        try {
            $classInfo = new \ReflectionClass($this);
            $this->name = $classInfo->getShortName();
            $this->root_path = dirname($classInfo->getFileName());
        } catch (\Exception $exception) {
            throw new ConfigError("", 0, $exception);
        }

        // Deploy the module's file structure
        $this->deploy();

        try {
            $renderer = AbstractRenderer::fromRuntime($this);
            $renderer->addTemplateFolder($this->name, Path::build($this->root_path, "templates"));
        } catch (PlexusError $e) {}

        $this->registerServices();
        $this->registerControlers();
        $this->registerRoutes();
    }

    protected function deploy() {
        $this->_mkdir("Controlers");
        $this->_mkdir("Forms");
        $this->_mkdir("Services");
        $this->_mkdir("templates");
    }

    /**
     *
     */
    public function onRun() {

    }

    /**
     * @throws ConfigError
     */
    protected function registerControlers() {

        $this->controlers = new Collection();

        $controlerDir = Path::build($this->root_path, "Controlers");
        $controler_files = scandir($controlerDir);

        try {
            $classInfo = new \ReflectionClass($this);
            $this->name = $classInfo->getShortName();
            $this->root_path = dirname($classInfo->getFileName());
        } catch (\Exception $exception) {
            throw new ConfigError("", 0, $exception);
        }

        if ($controler_files) {
            foreach ($controler_files as $controler_file) {
                if ($controler_file[0] == '.' || !RegExp::matches('/(.*)\.php$/', $controler_file)) {
                    continue;
                }
                $controler_name = str_replace('.php', '', $controler_file);
                $controler_class = '\\'.$classInfo->getNamespaceName().'\\Controlers\\'.$controler_name;
                if (!class_exists($controler_class)) {
                    throw new ConfigError('Aucune classe nommée "'.$controler_class.'" n\'a été trouvée.');
                }
                $this->controlers->set($controler_name, new $controler_class($this->application));
            }
        }
    }

    protected function registerRoutes() {
        $routeConfiguration = new Configuration(Path::build($this->root_path, "routes.ini"));

        $routeConfiguration->each(function($route_name, Collection $route) {
            $router = Router::fromRuntime($this);
            $route->set('name', $route_name);

            $action_identifier = $route->get('action');
            $actionComponents = Route::parse_action_identifier($action_identifier);
            $route->set('module', $actionComponents['module'] ? $this->getModule($actionComponents['module']) : $this);
            $route->set('controler', $route->get('module')->getControler($actionComponents['controler']));
            $route->set('action', $actionComponents['action']);

            if (!method_exists($route->get('controler'), $route->get('action'))) {
                throw new ConfigError(Text::format("Aucune action ne correspond à '{}'", $action_identifier));
            }

            $router->registerRoute($route);
        });


    }

    protected function registerServices() {

    }

    public function middleware() {

    }

    /**
     * @param $name
     * @return Controler
     */
    public function getControler($name) {
        if (!$this->controlers->isset($name)) {
            throw new PlexusError(Text::format("Aucun controleur nommé '{}' n'a pu être trouvé dans le module '{}'.", $name, $this->name));
        }
        return $this->controlers->get($name);
    }

    private function _mkdir($dirname) {
        $dir = Path::build($this->root_path, $dirname);
        if (!is_dir($dir)) {
            mkdir($dir);
        }
    }

}