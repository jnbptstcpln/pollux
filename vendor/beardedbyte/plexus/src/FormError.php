<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 03/03/2020
 * Time: 12:46
 */

namespace Plexus;


class FormError {

    static $DISPLAY_INLINE = 1;
    static $DISPLAY_GLOBAL = 2;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var int
     */
    protected $display;

    /**
     * FormError constructor.
     * @param $message
     * @param null $display
     */
    public function __construct($message, $display=null) {
        $this->message = $message;
        $this->display = ($display !== null) ? $display : $this::$DISPLAY_INLINE;
    }

    /**
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @return int|null
     */
    public function getDisplay() {
        return $this->display;
    }

    /**
     * @return bool
     */
    public function isInline() {
        return $this->display == $this::$DISPLAY_INLINE;
    }

    /**
     * @return bool
     */
    public function isGlobal() {
        return $this->display == $this::$DISPLAY_GLOBAL;
    }

}