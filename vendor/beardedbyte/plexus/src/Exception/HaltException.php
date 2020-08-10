<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 18/02/2020
 * Time: 22:00
 */

namespace Plexus\Exception;


class HaltException extends PlexusException {
    public function __construct($code, $message=null) {
        parent::__construct($message, $code);
    }
}