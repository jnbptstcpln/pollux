<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 18/02/2020
 * Time: 21:42
 */

namespace Plexus;


use Plexus\Service\AbstractService;

abstract class AbstractRuntime {

    /**
     * @var Application
     */
    public $application;

    /**
     * AbstractRuntime constructor.
     * @param Application $application
     */
    public function __construct(Application $application) {
        $this->application = $application;
    }

    /*
     * ----- RUNTIME ACTION -----
     */

    /**
     * @param $data
     * @param $type
     */
    public function log($data, $type=null) {
        $this->application->log($data, $type);
    }

    /**
     * @param $message
     * @param $type
     * @param array $options
     */
    public function flash($message, $type, $options=[]) {
        $this->application->flash($message, $type, $options);
    }

    /**
     * @param $code
     * @param null $message
     * @throws Exception\HaltException
     */
    public function halt($code=null, $message=null) {
        $this->application->halt($code, $message);
    }

    /**
     * @param $url
     * @throws Exception\HaltException
     */
    public function redirect($url) {
        $this->application->redirect($url);
    }

    /**
     * @throws Exception\HaltException
     */
    public function refresh() {
        $this->application->refresh();
    }

    /**
     * @param $route_name
     * @param mixed ...$params
     * @return string
     */
    public function uriFor($route_name, ...$params) {
        return $this->application->uriFor($route_name, ...$params);
    }

    /**
     * @param null $env
     * @return bool|string
     */
    public function env($env=null) {
        return $this->application->env($env);
    }

    /**
     * @param null $method
     * @return bool|string
     */
    public function method($method=null) {
        if ($method) {
            return strtolower($method) == strtolower($this->getRequest()->method());
        } else {
            return strtolower($this->getRequest()->method());
        }
    }

    /*
     * ----- RUNTIME VARIABLE -----
     */

    /**
     * @return Application
     */
    public function getApplication() {
        return $this->application;
    }

    /**
     * @return Request
     */
    public function getRequest() {
        return $this->application->getRequest();
    }

    /**
     * @return Response
     */
    public function getResponse() {
        return $this->application->getResponse();
    }

    /**
     * @return Session
     */
    public function getSession() {
        return $this->application->getSession();
    }

    /**
     * @param $name
     * @param bool $override
     * @return Configuration
     */
    public function getConfiguration($name, $override=false) {
        return $this->application->getConfiguration($name, $override);
    }

    /**
     * @param $name
     * @return AbstractService
     */
    public function getService($name) {
        return $this->application->getService($name);
    }

    /**
     * @param $name
     * @param bool $override
     * @return Configuration
     */
    public function getGlobalConfiguration($name, $override=false) {
        return $this->application->getGlobalConfiguration($name, $override);
    }

    /**
     * @param $name
     * @return ModelManager
     */
    public function getModelManager($name) {
        return $this->application->getModelManager($name);
    }

    /**
     * @param $name
     * @return Module
     */
    public function getModule($name) {
        return $this->application->getModule($name);
    }

}