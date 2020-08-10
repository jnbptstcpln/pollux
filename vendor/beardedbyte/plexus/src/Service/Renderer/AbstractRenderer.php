<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 03/03/2020
 * Time: 09:48
 */

namespace Plexus\Service\Renderer;


use Plexus\AbstractRuntime;
use Plexus\Application;
use Plexus\Error\PlexusError;
use Plexus\Exception\PlexusException;
use Plexus\Service\AbstractService;
use Plexus\Service\Router;
use Plexus\Utils\Text;

abstract class AbstractRenderer extends AbstractService implements RendererWrapperInterface {

    public function __construct(Application $application) {
        parent::__construct($application);
        $this->registerExtensions();
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
     *
     */
    public function registerExtensions() {
        $this->addGlobal('app', $this->application);

        $this->addFilter('render', function($element, ...$options) {
            $html = "";
            try {
                if (method_exists($element, 'render')) {
                    $html = $element->render(...$options);
                } else {
                    throw new PlexusError(Text::format("La méthode '{}:render' n'existe pas", get_class($element)));
                }
            } catch (\Throwable $e) {
                $this->application->log(new PlexusException(Text::format("Impossible d'effectuer le rendu de l'élément"), $e->getCode(), $e));
                if ($this->application->env('dev')) {
                    $html = "[[ Impossible d'effectuer le rendu de l'élément ]]";
                } else {
                    $html = "";
                }
            }
            return $html;
        }, ['is_safe' => array('html')]);

        $this->addFunction('current_url', function(...$params) {
            return $this->getRequest()->uri();
        });

        $this->addFunction('last_url', function(...$params) {
            return $this->getSession()->getLastURL();
        });

        $this->addFunction('route_url', function($routeName, ...$params) {
            return Router::fromRuntime($this)->uriFor($routeName, ...$params);
        });

        // Help to debug
        $this->addFunction('dump', function ($data) {
            return Text::format("<pre style='max-height: 200px; overflow-y: scroll;'>{}</pre>", var_export($data, true));
        }, ['is_safe' => array('html')]);
    }

    /**
     * @return string
     */
    public static function getServiceName() {
        return "Renderer";
    }

}