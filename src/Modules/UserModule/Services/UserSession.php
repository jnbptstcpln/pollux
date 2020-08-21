<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 12/03/2020
 * Time: 12:31
 */

namespace CPLN\Modules\UserModule\Services;


use Plexus\AbstractRuntime;
use Plexus\Model;
use Plexus\Service\AbstractService;
use Plexus\Service\Renderer\TwigRenderer;

class UserSession extends AbstractService {

    protected $user;

    /**
     * @param AbstractRuntime $runtime
     * @param mixed ...$options
     * @return self
     */
    public static function fromRuntime(AbstractRuntime $runtime, ...$options) {
        return parent::fromRuntime($runtime, $options);
    }

    /**
     * @throws \Plexus\Exception\ModelException
     */
    public function onRun() {

        $session = $this->getSession();
        $user_id = $session->get('user_id', null);

        if ($user_id) {
            $userManager = $this->getModelManager("user");
            $user = $userManager->id($user_id);

            if ($user) {
                $this->user = $user;
            } else {
                $this->destroyUserSession();
            }
        }

        $twig = TwigRenderer::fromRuntime($this);
        $twig->addGlobal("__user", $this->user);
    }

    /**
     * @param Model $user
     */
    public function openUserSession(Model $user) {
        $this->getSession()->set('user_id', $user->id);
        $this->user = $user;

        $twig = TwigRenderer::fromRuntime($this);
        $twig->addGlobal("__user", $this->user);
    }

    /**
     *
     */
    public function closeUserSession() {
        $this->getSession()->unset("user_id");

        $twig = TwigRenderer::fromRuntime($this);
        $twig->addGlobal("__user", $this->user);
    }

    /**
     *
     */
    public function destroyUserSession() {
        $this->getSession()->unset("user_id");
        session_destroy();
    }

    /**
     * @return bool
     */
    public function isConnected() {
        return $this->user !== null;
    }

    /**
     * @return mixed
     */
    public function getUser() {
        return $this->user;
    }

}