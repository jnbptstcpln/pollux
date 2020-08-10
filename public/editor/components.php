<?php

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

$components = [
    // event
    (new Component("event.HelloWorld", 2))
        ->addOuput("text")
    ,


    // system
    (new Component("system.Print", 0))
        ->addInput("value")
    ,
    (new Component("system.Exit", 0))
        ->addInput("value")
    ,
    (new Component("system.Concat", 1))
        ->addInput("string1")
        ->addInput("string2")
        ->addOuput("result")
    ,
    (new Component("system.Format", 1))
        ->addInput("value")
        ->addOuput("result")
        ->addSetting("template", "string")
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

    // environment
    (new Component("environment.Get", 0))
        ->addOuput("value", "string")
        ->addSetting("variable_name", "string")
    ,
    (new Component("environment.Set", 0))
        ->addInput("value", "string")
        ->addSetting("variable_name", "string")
    ,

    // logic
    (new Component("logic.Equals", 1))
        ->addInput("a", "string")
        ->addInput("b", "string")
        ->addOuput("true", "string")
        ->addOuput("false", "string")
    ,
    (new Component("logic.Greater", 1))
        ->addInput("a", "string")
        ->addInput("b", "string")
        ->addOuput("true", "string")
        ->addOuput("false", "string")
    ,
    (new Component("logic.Lesser", 1))
        ->addInput("a", "string")
        ->addInput("b", "string")
        ->addOuput("true", "string")
        ->addOuput("false", "string")
    ,
    (new Component("logic.Contains", 1))
        ->addInput("a", "string")
        ->addInput("b", "string")
        ->addOuput("true", "string")
        ->addOuput("false", "string")
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
    (new Component("api.oceane.Ticket", 2))
        ->addOuput("ticket", "string")
    ,
    (new Component("api.oceane.ImpactClient", 2))
        ->addInput("ticket_id", "string")
        ->addOuput("impact_technique", "string")
        ->addOuput("impact_client", "string")
    ,

];

echo json_encode($components);