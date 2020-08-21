<?php


namespace CPLN\Modules\IndexModule\Controlers;


use CPLN\Extensions\UserSession;
use Plexus\Controler;

class Index extends Controler {

    use UserSession;

    public function middleware() {
        $this->needLogin();
    }

    public function index() {
        $this->render("@IndexModule/index/index.html.twig");
    }

}