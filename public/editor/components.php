<?php

class Component {

    public $id;
    public $inputs = [];
    public $outputs = [];
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
}

$components = [
    (new Component("event.acme.HelloWorld", 2))
        ->addOuput("text")
    ,
    (new Component("system.Print", 2))
        ->addInput("value")
        ->addOuput("value")
    ,
    (new Component("math.Addition", 2))
        ->addInput("a", "float")
        ->addInput("b", "float")
        ->addOuput("result")
    ,

];

echo json_encode($components);