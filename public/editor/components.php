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
    // event
    (new Component("event.HelloWorld", 2))
        ->addOuput("text")
    ,
    // system
    (new Component("system.Print", 2))
        ->addInput("value")
        ->addOuput("value")
    ,
    (new Component("system.Exit", 2))
        ->addInput("value")
    ,
    // math
    (new Component("math.Addition", 2))
        ->addInput("a", "float")
        ->addInput("b", "float")
        ->addOuput("a+b")
    ,
    (new Component("math.Substraction", 2))
        ->addInput("a", "float")
        ->addInput("b", "float")
        ->addOuput("a-b")
    ,
    (new Component("math.Multiplication", 2))
        ->addInput("a", "float")
        ->addInput("b", "float")
        ->addOuput("a*b")
    ,
    (new Component("math.Division", 2))
        ->addInput("a", "float")
        ->addInput("b", "float")
        ->addOuput("a/b")
    ,
    // api.heimdall
    (new Component("api.heimdall.Execute", 2))
        ->addInput("switch", "string")
        ->addInput("command", "string")
        ->addOuput("result")
    ,
    // api.oceane
    (new Component("api.oceane.Ticket", 2))
        ->addInput("ticket_id", "string")
        ->addOuput("ticket", "dict")
    ,
    (new Component("api.oceane.RechercheRessource", 2))
        ->addInput("idt11", "string")
        ->addInput("idt21", "string")
        ->addInput("idt31", "string")
        ->addOuput("ressource", "dict")
    ,
    (new Component("api.oceane.ImpactClient", 2))
        ->addInput("ticket_id", "string")
        ->addOuput("impact_technique", "string")
        ->addOuput("impact_client", "string")
    ,

];

echo json_encode($components);