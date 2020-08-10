<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 03/03/2020
 * Time: 09:41
 */

namespace Plexus;


interface Component {

    /**
     * @param array $options
     * @return mixed
     */
    public function render($options=[]);

}