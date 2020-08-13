<?php

namespace CPLN\Modules\EditorModule\Controlers;


use CPLN\Services\Library;
use Plexus\Controler;
use Plexus\Exception\ModelException;
use Plexus\Exception\PlexusException;
use Plexus\ModelSelector;
use Plexus\Utils\Randomizer;
use Plexus\Utils\Text;

class Flow extends Controler {

    public function all() {

        $flowManager = $this->getModelManager("flow");
        $flows = $flowManager->select(
            ModelSelector::order("last_update", "DESC")
        );

        $this->render("@EditorModule/flow/all.html.twig", [
            "flows" => $flows
        ]);
    }

    public function add() {

        $identifier = Randomizer::generate_unique_token(20, function ($value) {
           return $this->getModelManager("flow")->get(["identifier" => $value]) === null;
        });

        $flowManager = $this->getModelManager("flow");
        $flow = $flowManager->create();

        $flow->name = "Sans titre";
        $flow->identifier = $identifier;
        $flow->scheme = json_encode(
            [
                "nodes" => [],
                "links" => [],
                "positions" => [],
                "settings" => [
                    "name" => $flow->name
                ],
            ]
        );

        try {
            $flowManager->insert($flow, [
                'last_update' => "NOW()"
            ]);
            $this->redirect($this->uriFor("editor-flow-edit", $identifier));
        } catch (ModelException $e) {
            $this->flash("Une erreur est survenue lors de la mise à jour de la base de données.", "error");
            $this->redirect($this->uriFor("editor-flow-all"));
        }
    }

    public function edit($identifier) {
        $flowManager = $this->getModelManager("flow");
        $flow = $flowManager->get(['identifier' => $identifier]);
        if (!$flow) {
            $this->halt(404);
        }

        $this->render("@EditorModule/flow/edit.html.twig", [
            "flow" => $flow
        ]);
    }

    public function download($identifier) {
        $flowManager = $this->getModelManager("flow");
        $flow = $flowManager->get(['identifier' => $identifier]);
        if (!$flow) {
            $this->halt(404);
        }

        $response = $this->getResponse();
        $response->setStatusCode(200);
        $response->header('Content-type', "application/octet-stream");
        $response->header('Content-Disposition', 'attachment; filename="'.Text::slug($flow->name).'.json"');
        $response->body($flow->scheme);
        $response->send();
    }

}