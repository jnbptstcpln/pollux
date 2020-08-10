<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 03/03/2020
 * Time: 09:49
 */

namespace Plexus\Service\Renderer;


use Plexus\Exception\PlexusException;

interface RendererWrapperInterface {
    /**
     * @param $template
     * @param $data
     * @return mixed
     * @throws PlexusException
     */
    function render($template, $data=[]);

    /**
     * @param $name
     * @param $path
     * @return mixed
     */
    public function addTemplateFolder($name, $path);

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    function addGlobal($name, $value);

    /**
     * @param $name
     * @param $function
     * @param $options
     * @return mixed
     */
    function addFunction($name, $function, $options=[]);

    /**
     * @param $name
     * @param $function
     * @param $options
     * @return mixed
     */
    function addFilter($name, $function, $options=[]);
}