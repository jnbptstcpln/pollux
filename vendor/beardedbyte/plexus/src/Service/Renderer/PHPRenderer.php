<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 03/03/2020
 * Time: 10:00
 */

namespace Plexus\Service\Renderer;


use Plexus\Application;

class PHPRenderer extends AbstractRenderer implements RendererWrapperInterface {

    /**
     * @var
     */
    protected $renderer;

    /**
     * PHPRenderer constructor.
     * @param Application $application
     * @param $template_folder
     * @throws \Exception
     */
    public function __construct(Application $application, $template_folder) {
        parent::__construct($application);
        $this->renderer = new Renderer($template_folder);
    }

    /**
     * @param $template
     * @param array $data
     * @return mixed|string
     * @throws \Throwable
     */
    public function render($template, $data=[]) {
        return $this->renderer->render($template, $data);
    }

    /**
     * @param $name
     * @param $path
     * @return mixed|void
     * @throws \Exception
     */
    public function addTemplateFolder($name, $path) {
        $this->renderer->addTemplateFolder($name, $path);
    }

    /**
     * @param $name
     * @param $value
     * @return mixed|void
     */
    public function addGlobal($name, $value) {
        $this->renderer->addGlobal($name, $value);
    }

    /**
     * @param $name
     * @param $function
     * @param $options
     * @return mixed|void
     */
    public function addFunction($name, $function, $options=[]) {
        $this->renderer->addFunction($name, $function, $options);
    }

    /**
     * @param $name
     * @param $function
     * @param $options
     * @return mixed|void
     */
    public function addFilter($name, $function, $options=[]) {
        $this->renderer->addFilter($name, $function, $options=[]);
    }

}