<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 12/03/2020
 * Time: 12:30
 */

namespace CPLN\Modules\UserModule;


use CPLN\Modules\UserModule\Services\UserSession;
use Plexus\Module;

class UserModule extends Module {

    protected function registerServices() {
        UserSession::fromRuntime($this);
    }

}