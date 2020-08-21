<?php


namespace CPLN\Modules\UserModule\Controlers;


use CPLN\Modules\UserModule\Forms\AccountForm;
use Plexus\Controler;
use Plexus\FormField\PasswordInput;
use Plexus\ModelSelector;
use Plexus\Service\Renderer\TwigRenderer;
use Plexus\Utils\Text;

class Manage extends Controler {

    public function middleware() {
        TwigRenderer::fromRuntime($this)->addGlobal("lefbarnav_active", "user");
    }

    public function index() {
        $users = $this->getModelManager("user")->select(ModelSelector::order("last_name"));
        $this->render("@UserModule/manage/index.html.twig", [
            'users' => $users
        ]);
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

                $username = $user->username;
                $acc = 1;
                while ($userManager->get(['username' => $username])) {
                    $acc += 1;
                    $username = $user->username.$acc;
                }
                $user->username = $username;

                $userManager->insert($user);
                $this->flash("L'utilisateur a été ajouté avec succès !", "success");
                $this->redirect($this->uriFor("user-manage-edit", $user->username));
            }
        }

        $this->render("@UserModule/manage/create.html.twig", [
            'form' => $form
        ]);

    }

    public function edit($username) {
        $userManager = $this->getModelManager("user");
        $user = $userManager->get(['username' => $username]);
        if (!$user) {
            $this->halt(404);
        }

        $form = new AccountForm($this);
        $form->fillWithModel($user);

        if ($this->method("post")) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {
                $user = $userManager->create();
                $user->updateFromForm($form);

                $userManager->update($user);
                $this->flash("Les changements ont bien été sauvegardés", "success");
                $this->refresh();
            }
        }


        $this->render("@UserModule/manage/edit.html.twig", [
            'user' => $user,
            'form' => $form
        ]);

    }

    public function delete($username) {
        $userManager = $this->getModelManager("user");
        $user = $userManager->get(['username' => $username]);

        if ($user) {
            $userManager->delete($user);
            $this->flash("L'utilisateur a bien été supprimé", "info");
        }

        $this->redirect($this->uriFor("user-manage-index"));
    }

}