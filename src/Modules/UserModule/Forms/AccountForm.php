<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 12/05/2020
 * Time: 00:45
 */

namespace CPLN\Modules\UserModule\Forms;


use Plexus\AbstractRuntime;
use Plexus\Form;
use Plexus\FormField\CSRFInput;
use Plexus\FormField\EmailInput;
use Plexus\FormField\TextInput;
use Plexus\FormValidator\LengthMaxValidator;

class AccountForm extends Form {

    public function __construct(AbstractRuntime $runtime) {
        parent::__construct($runtime);

        $this
            ->setMethod("post")
        ;

        $this
            ->addField(new CSRFInput("account-edit"))
            ->addField(new TextInput("first_name", [
                'label' => "Prénom",
                'required' => true,
                'validators' => [
                    new LengthMaxValidator(30)
                ]
            ]))
            ->addField(new TextInput("last_name", [
                'label' => "Nom",
                'required' => true,
                'validators' => [
                    new LengthMaxValidator(50)
                ]
            ]))
            ->addField(new EmailInput("email", [
                'label' => "Adresse mail",
                'required' => true,
                'validators' => [
                    new LengthMaxValidator(255)
                ]
            ]))
        ;
    }

}