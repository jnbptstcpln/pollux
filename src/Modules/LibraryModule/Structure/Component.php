<?php


namespace CPLN\Modules\LibraryModule\Structure;


class Component {

    public $id;
    public $inputs = [];
    public $outputs = [];
    public $settings = [];
    public $requirements = [];
    public $size = 1;
    public $description = "";

    public function __construct($id, $size=1, $description="") {
        $this->id = $id;
        $this->size = $size;
        $this->description = $description;
    }

    public function addInput($name, $type="string", $description="") {
        $this->inputs[] = ['name' => $name, 'type' => $type, 'description' => $description];
        return $this;
    }

    public function addOuput($name, $type="string", $description="") {
        $this->outputs[] = ['name' => $name, 'type' => $type, 'description' => $description];
        return $this;
    }

    public function addSetting($name, $type="string", $description="") {
        $this->settings[] = ['name' => $name, 'type' => $type, 'description' => $description];
        return $this;
    }

    public function addRequirement($name) {
        $this->requirements[] = ['name' => $name];
        return $this;
    }

}