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
use Plexus\Exception\PlexusException;
use Plexus\Service\Renderer\AbstractRenderer;
use Plexus\Service\Renderer\Renderer;
use Plexus\Utils\Path;
use Plexus\Utils\RegExp;
use Plexus\Utils\Text;

class Controler extends AbstractRuntime {

    protected $name;

    public function __construct(Application $application) {
        parent::__construct($application);

        try {
            $classInfo = new \ReflectionClass($this);
            $this->name = $classInfo->getShortName();
        } catch (\Exception $exception) {
            throw new ConfigError("", 0, $exception);
        }
    }

    public function middleware() {

    }

    /**
     * @return Collection
     */
    public function paramsPost() {
        return $this->getRequest()->paramsPost();
    }

    /**
     * @return Collection
     */
    public function paramsGet() {
        return $this->getRequest()->paramsGet();
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function paramPost($name, $default=null) {
        return $this->getRequest()->paramPost($name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function paramGet($name, $default=null) {
        return $this->getRequest()->paramGet($name, $default);
    }

    /**
     * @param $template
     * @param array $data
     * @param bool $as_string
     * @return mixed
     * @throws Exception\PlexusException
     */
    public function render($template, $data=[], $as_string=false) {
        try {
            $renderer = AbstractRenderer::fromRuntime($this);
            $html = $renderer->render($template, $data);
            if ($as_string) {
                return $html;
            } else {
                $this->getResponse()->body($html);
            }
        } catch (\Throwable $e) {
            throw new PlexusException("Une erreur a eu lieu lors du rendu", 0, $e);
        }

    }

}