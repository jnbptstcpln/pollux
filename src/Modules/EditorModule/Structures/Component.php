<?php


namespace CPLN\Modules\EditorModule\Structures;


class Component {

    public $id;
    public $inputs = [];
    public $outputs = [];
    public $settings = [];
    public $size = 1;

    public function __construct($id, $size=1) {
        $this->id = $id;
        $this->size = $size;
    }

    public function addInput($name, $type="string") {
        $this->inputs[] = ['name' => $name, 'type' => $type];
        return $this;
    }

    public function addOuput($name, $type="string") {
        $this->outputs[] = ['name' => $name, 'type' => $type];
        return $this;
    }

    public function addSetting($name, $type="string") {
        $this->settings[] = ['name' => $name, 'type' => $type];
        return $this;
    }

}