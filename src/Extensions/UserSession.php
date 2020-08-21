<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 12/03/2020
 * Time: 13:47
 */

namespace CPLN\Extensions;


use Plexus\Model;

trait UserSession {

    /**
     * @return bool
     */
    public function isUserConnected() {
        return \CPLN\Modules\UserModule\Services\UserSession::fromRuntime($this)->isConnected();
    }

    /**
     * @return Model
     */
    public function getConnectedUser() {
        return \CPLN\Modules\UserModule\Services\UserSession::fromRuntime($this)->getUser();
    }

    /**
     * @param bool $push_current_url
     */
    public function needLogin($push_current_url=true) {
        if (!$this->isUserConnected()) {
            $this->getSession()->pushCurrentURL();
            $this->redirect($this->uriFor("user-login"));
        }
    }

    /**
     * @param Model $user
     */
    public function openUserSession(Model $user) {
        return \CPLN\Modules\UserModule\Services\UserSession::fromRuntime($this)->openUserSession($user);
    }

    /**
     *
     */
    public function closeUserSession() {
        return \CPLN\Modules\UserModule\Services\UserSession::fromRuntime($this)->closeUserSession();
    }

}