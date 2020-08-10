<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 03/03/2020
 * Time: 10:00
 */

namespace Plexus\Service\Renderer;


use Plexus\Application;
use Plexus\Exception\PlexusException;
use Plexus\Utils\Text;

class TwigRenderer extends AbstractRenderer implements RendererWrapperInterface {

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var \Twig_Loader_Filesystem
     */
    protected $twig_loader;

    /**
     * TwigRenderer constructor.
     * @param Application $application
     * @param $template_folder
     * @throws \ReflectionException
     */
    public function __construct(Application $application, $template_folder) {
        $this->twig_loader = new \Twig_Loader_Filesystem($template_folder);
        $this->twig = new \Twig_Environment($this->twig_loader);
        parent::__construct($application);
    }

    /**
     * @param $template
     * @param array $data
     * @return string
     * @throws PlexusException
     */
    public function render($template, $data=[]) {
        try {
            return $this->twig->render($template, $data);
        } catch (\Throwable $e) {
            throw new PlexusException(Text::format("Erreur lors du rendu du template '{}'", $template), 0, $e);
        }

    }

    /**
     * @param $name
     * @param $path
     * @return mixed|void
     * @throws \Twig_Error_Loader
     */
    public function addTemplateFolder($name, $path) {
        $this->twig_loader->addPath($path, $name);
    }

    /**
     * @param $name
     * @param $value
     */
    public function addGlobal($name, $value) {
        $this->twig->addGlobal($name, $value);
    }

    /**
     * @param $name
     * @param $function
     * @param $options
     */
    public function addFunction($name, $function, $options=[]) {
        $this->twig->addFunction(new \Twig\TwigFunction($name, $function, $options));
    }

    /**
     * @param $name
     * @param $function
     * @param $options
     */
    public function addFilter($name, $function, $options=[]) {
        $this->twig->addFilter(new \Twig\TwigFilter($name, $function, $options));
    }

}