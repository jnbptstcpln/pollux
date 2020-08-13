<?php


namespace CPLN\Modules\EditorModule\Controlers;


use CPLN\Modules\EditorModule\Structures\Component;
use Plexus\Controler;
use Plexus\ControlerAPI;
use Plexus\DataType\Collection;
use Plexus\Exception\ModelException;

class Library extends Controler {

    use ControlerAPI;

    public function components() {

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
            (new Component("system.Concat", 0))
                ->addInput("string1")
                ->addInput("string2")
                ->addOuput("result")
            ,
            (new Component("system.Format", 0))
                ->addInput("value")
                ->addOuput("result")
                ->addSetting("template", "string")
            ,
            (new Component("system.Log", 0))
                ->addInput("value")
            ,
            (new Component("system.Sleep", 0))
                ->addInput("value")
                ->addOuput("value")
                ->addSetting("duration", "float")
            ,

            // math
            (new Component("math.Addition", 0))
                ->addInput("a", "float")
                ->addInput("b", "float")
                ->addOuput("a+b")
            ,
            (new Component("math.Substraction", 0))
                ->addInput("a", "float")
                ->addInput("b", "float")
                ->addOuput("a-b")
            ,
            (new Component("math.Multiplication", 0))
                ->addInput("a", "float")
                ->addInput("b", "float")
                ->addOuput("a*b")
            ,
            (new Component("math.Division", 0))
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

        $this->success($components);

    }

    public function flow($identifier) {

        $flowManager = $this->getModelManager("flow");
        $flow = $flowManager->get(['identifier' => $identifier]);
        if (!$flow) {
            $this->halt(404);
        }

        if ($this->method("get")) {
            $this->success(json_decode($flow->scheme));
        } else {
            $content_array = json_decode($this->paramPost("scheme"), true);
            $content = json_decode($this->paramPost("scheme"));
            if (!is_array($content_array)) {
                $this->error(400, "Veuillez un schéma correct.");
            }

            $scheme = new Collection($content_array);
            $settings = $scheme->get("settings", new Collection());

            $flow->name = $settings->get("name", "Sans titre");
            $flow->scheme = json_encode($content);

            try {
                $flowManager->update($flow, [
                    'last_update' => "NOW()"
                ]);
                $this->success(null);
            } catch (ModelException $e) {
                $this->error(400, "Une erreur est survenue lors de la mise à jour de la base de données.");
            }
        }
    }

}