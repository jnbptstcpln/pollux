<?php


namespace CPLN\Modules\UserModule\Controlers;


use CPLN\Extensions\UserSession;
use CPLN\Modules\UserModule\Forms\AccountForm;
use Plexus\Controler;
use Plexus\Form;
use Plexus\FormField\PasswordInput;
use Plexus\Utils\Text;

class Configure extends Controler {

    use UserSession;

    public function middleware() {
        $userManager = $this->getModelManager("user");
        if ($userManager->count() > 0) {
            $this->redirect($this->uriFor("user-login"));
        }
    }

    public function create() {
        $userManager = $this->getModelManager("user");

        $form = new AccountForm($this);

        $form->addField(new PasswordInput("password", [
            'label' => "Mot de passe",
            'required' => true
        ]));

        if ($this->method("post")) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {
                $user = $userManager->create();
                $user->updateFromForm($form);
                $user->username = Text::format(
                    "{}.{}",
                            Text::slug($user->first_name),
                            Text::slug($user->last_name)
                );
                $user->password = password_hash($user->password, PASSWORD_DEFAULT);
                $userManager->insert($user);
                $this->openUserSession($user);
                $this->redirect($this->getSession()->getLastURL());
            }
        }

        $this->render("@UserModule/configure/create.html.twig", [
            'form' => $form
        ]);
    }

}