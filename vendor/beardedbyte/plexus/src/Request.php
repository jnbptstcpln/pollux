<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 18/02/2020
 * Time: 22:04
 */

namespace Plexus;


use Plexus\DataType\Collection;

class Request {

    /**
     * @var Collection
     */
    protected $params_get;

    /**
     * @var Collection
     */
    protected $params_post;

    /**
     * @var Collection
     */
    protected $cookies;

    /**
     * @var Collection
     */
    protected $server;

    /**
     * @var Collection
     */
    protected $headers;

    /**
     * @var Collection
     */
    protected $files;

    /**
     * @var null|string
     */
    protected $body;

    /**
     * Request constructor.
     * @param array $params_get
     * @param array $params_post
     * @param array $cookies
     * @param array $server
     * @param array $files
     * @param null $body
     */
    public function __construct($params_get=null, $params_post=null, $cookies=null, $server=null, $files=null, $body=null) {
        $this->params_get = new Collection($params_get !== null ? $params_get : $_GET);
        $this->params_post = new Collection($params_post !== null ? $params_post : $_POST);
        $this->cookies = new Collection($cookies !== null ? $cookies : $_COOKIE);
        $this->server = new Collection($server !== null ? $server : $_SERVER);

        // Build headers from server collection
        $this->headers = new Collection();
        foreach ($this->server->getArray() as $key => $value) {
            if (strpos($key, 'HTTP_') == 0) {
                $this->headers->set(substr($key, strlen('HTTP_')), $value);
            } elseif (in_array($key, ['CONTENT_LENGTH', 'CONTENT_TYPE', 'CONTENT_MD5'])) {
                $this->headers->set($key, $value);
            }
        }

        $this->files = new Collection($files !== null ? $files : $_FILES);
        $this->body = $body ? (string) $body : null;
    }

    /**
     * @return Collection
     */
    public function paramsGet() {
        return $this->params_get;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function paramGet($name, $default=null) {
        return $this->params_get->get($name, $default);
    }

    /**
     * @return Collection
     */
    public function paramsPost() {
        return $this->params_post;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function paramPost($name, $default=null) {
        return $this->params_post->get($name, $default);
    }

    /**
     * @return Collection
     */
    public function cookies() {
        return $this->cookies;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function cookie($name, $default=null) {
        return $this->cookies->get($name, $default);
    }

    /**
     * @return Collection
     */
    public function server() {
        return $this->server;
    }

    /**
     * @return Collection
     */
    public function headers() {
        return $this->headers;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function header($name, $default=null) {
        return $this->headers->get($name, $default);
    }

    /**
     * @return Collection
     */
    public function files() {
        return $this->files;
    }

    /**
     * @return bool
     */
    public function isSecure() {
        return ($this->server->get('HTTPS') == true);
    }

    /**
     * @return mixed|null
     */
    public function ip() {
        return $this->server->get('REMOTE_ADDR');
    }

    /**
     * @return mixed|null
     */
    public function uri() {
        return $this->server->get('REQUEST_URI', '/');
    }

    /**
     * @return mixed|null
     */
    public function pathname() {
        $uri = $this->uri();
        $uri = strstr($uri, '?', true) ?: $uri;
        return $uri;
    }

    /**
     * @param null $is
     * @return bool|mixed|null
     */
    public function method($is=null) {
        $method = $this->server->get('REQUEST_METHOD', 'GET');

        if (null !== $is) {
            return strcasecmp($method, $is) === 0;
        }

        return $method;
    }

}