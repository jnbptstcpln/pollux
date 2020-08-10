<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 18/02/2020
 * Time: 22:41
 */

namespace Plexus\Service;


use Plexus\AbstractRuntime;
use Plexus\Application;
use Plexus\DataType\Collection;
use Plexus\Error\ConfigError;
use Plexus\Exception\HaltException;
use Plexus\Route;
use Plexus\Utils\Text;

class Router extends AbstractService {

    protected $routes;

    protected $propagation_stopped = false;

    protected $current_route = null;

    protected $routes_matched = [];

    /**
     * Router constructor.
     * @param Application $application
     * @throws \ReflectionException
     */
    public function __construct(Application $application) {
        parent::__construct($application);
        $this->routes = new Collection();
    }

    /**
     * @param AbstractRuntime $runtime
     * @param mixed ...$options
     * @return self
     */
    public static function fromRuntime(AbstractRuntime $runtime, ...$options) {
        return parent::fromRuntime($runtime, ...$options);
    }

    /**
     * @throws HaltException
     */
    public function dispatch() {
        $this->routes->each(function($name, Route $route) {
            if (!$this->propagation_stopped) {
                $params = [];

                if ($route->matches($this->getRequest()->method(), explode('?', $this->getRequest()->uri())[0], $params)) {

                    $this->current_route = $route;
                    if (!$route->matchesAllPattern()) {
                        $this->routes_matched[] = $route;
                    }

                    $route->getAction()(...array_values($params));

                }
            }
        });

        $this->current_route = null;

        if (!$this->getResponse()->isSent()) {
            if ($this->getResponse()->getStatusCode() == 0) {
                if (count($this->routes_matched) > 0) {
                    $this->getResponse()->setStatusCode(200);
                } else {
                    $this->halt(404, "Not found");
                }
            }
            $this->getResponse()->send();
        }
    }

    /**
     * @param $routeName
     * @param mixed ...$params
     * @return string
     */
    public function uriFor($routeName, ...$params) {
        try {
            $route = $this->getRoute($routeName);
            return $route->build_uri(...$params);
        } catch (\Throwable $e) {
            $this->log($e);
            return $this->getApplication()->base_url();
        }
    }

    /**
     *
     */
    public function stopPropagation() {
        $this->propagation_stopped = true;
    }

    /**
     * @param array|Collection $route
     * @throws \Exception
     */
    public function registerRoute($route) {
        $route = new Collection($route);

        $module = $route->get('module');
        $controler = $route->get('controler');
        $action = $route->get('action');

        $this->addRoute(
            new Route(
                $route->get('name'),
                $route->get('method', '*'),
                $this->getApplication()->base_url().$route->get('path'),
                function(...$params) use ($module, $controler, $action) {
                    $module->middleware();
                    if (!$this->propagation_stopped) {
                        $controler->middleware();
                    }
                    if (!$this->propagation_stopped) {
                        $controler->$action(...$params);
                    }
                }
            )
        );
    }

    /**
     * @param Route $route
     * @return $this
     * @throws \Exception
     */
    public function addRoute(Route $route) {
        if ($this->routes->get($route->getName()) !== null) {
            throw new \Exception('Une route est déjà enregistrée sous le nom "'.$route->getName().'".');
        }
        $this->routes->set($route->getName(), $route);

        return $this;
    }

    /**
     * @param string $name
     * @return Route
     * @throws \Exception
     */
    public function getRoute($name) {
        if ($this->routes->get($name) === null) {
            throw new \Exception('Aucun modèle de route nommé "'.$name.'" n\'a été trouvé.');
        }
        return $this->routes->get($name);
    }

    /**
     * @return Collection
     */
    public function getRoutes() {
        return $this->routes;
    }

    /**
     * @return null|Route
     */
    public function getCurrentRoute() {
        return $this->current_route;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped() {
        return $this->propagation_stopped;
    }

}