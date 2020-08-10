<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 11/03/2020
 * Time: 13:12
 */

namespace CPLN;


use CPLN\Services\APIMiddleware;
use CPLN\Services\Assets;
use Plexus\Service\Renderer\TwigRenderer;
use Plexus\Utils\Path;

class Application extends \Plexus\Application {

    protected function registerServices() {
        $twig = TwigRenderer::fromRuntime($this, Path::build($this->root_path, "src", "templates"));
        $assets = Assets::fromRuntime($this);
        // Call APIMiddleware to add a new middleware to the router before adding modules' routes
        $apiMiddleware = APIMiddleware::fromRuntime($this);
    }

    protected function onRun() {

    }

    /**
     * @param $name
     * @return string
     */
    public function getGlobal($name) {
        $configuration = $this->getGlobalConfiguration('globals');
        return $configuration->get($name);
    }

}