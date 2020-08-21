<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 12/03/2020
 * Time: 12:30
 */

namespace CPLN\Modules\UserModule\Controlers;


use CPLN\Extensions\UserSession;
use Plexus\Controler;
use Plexus\Form;
use Plexus\FormField\CSRFInput;
use Plexus\FormField\PasswordInput;
use Plexus\FormField\TextInput;
use Plexus\Utils\Text;

class Session extends Controler {

    use UserSession;

    public function middleware() {
        // If no user is set in the databse, redirect to a configure page
        $userManager = $this->getModelManager("user");
        if ($userManager->count() == 0) {
            $this->redirect($this->uriFor("user-configure"));
        }
    }

    /**
     * @throws \Plexus\Exception\HaltException
     */
    public function login() {
        if ($this->isUserConnected()) {
            $this->redirect($this->getSession()->getLastURL());
        }

        $form = new Form($this);
        $form->setMethod("post");
        $form
            ->addField(new CSRFInput("login"))
            ->addField(new TextInput("username", [
                'label' => 'Identifiant utilisateur',
                'required' => true
            ]))
            ->addField(new PasswordInput("password", [
                'label' => 'Mot de passe',
                'required' => true
            ]))
        ;

        if ($this->method("post")) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {
                $username = $form->getValueOf("username");
                $password = $form->getValueOf("password");
                $user = $this->getModelManager("user")->get(['username' => $username]);
                if ($user) {
                    if (password_verify($password, $user->password)) {
                        $this->openUserSession($user);
                        $this->redirect($this->getSession()->getLastURL());
                    }
                }
                $form->addError("La combinaison nom d'utilisation/mot de passe que vous avez fourni est incorrecte.");
            }
        }

        $this->render("@UserModule/session/login.html.twig", [
            'form' => $form
        ]);
    }

    /**
     * @throws \Plexus\Exception\HaltException
     */
    public function logout() {
        if (!$this->isUserConnected()) {
            $this->redirect($this->uriFor("index"));
        }

        $this->closeUserSession();

        $url = $this->uriFor("index");
        if ($this->paramsGet()->isset("redirect")) {
            $url = $this->getSession()->getLastURL();
        }
        $this->redirect($url);
    }

}