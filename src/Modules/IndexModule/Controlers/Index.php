<?php


namespace CPLN\Modules\IndexModule\Controlers;


use Plexus\Controler;

class Index extends Controler {

    public function index() {
        $this->render("@IndexModule/index/index.html.twig");
    }

}