<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:57
 */

namespace Plexus\FormField;


use Plexus\FormValidator\EmailValidator;

class EmailInput extends Input {

    public function __construct($id, $settings=[]) {
        parent::__construct($id, 'email', $settings);
        $this->addValidator(new EmailValidator());
    }

}