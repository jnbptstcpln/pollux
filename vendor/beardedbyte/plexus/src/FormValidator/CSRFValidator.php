<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\FormValidator;


use Plexus\AbstractRuntime;
use Plexus\FormError;
use Plexus\Session;


class CSRFValidator extends AbstractValidator {

    /**
     * @var string
     */
    protected $identifier;

    /**
     * CSRF constructor.
     * @param $identifier
     */
    public function __construct($identifier) {
        parent::__construct("Potentielle attaque CSRF détectée, veuillez réessayer.", function($value) {
            return $this->runtime->getSession()->check_crsf_token($this->identifier, $value);
        }, false, FormError::$DISPLAY_GLOBAL);
        $this->identifier = $identifier;
    }
}