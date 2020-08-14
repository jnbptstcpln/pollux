<?php


namespace CPLN\Modules\EditorModule\Controlers;


use CPLN\Modules\EditorModule\Structures\Component;
use CPLN\Modules\LibraryModule\Services\ModuleService;
use Plexus\Controler;
use Plexus\ControlerAPI;
use Plexus\DataType\Collection;
use Plexus\Exception\ModelException;

class Library extends Controler {

    use ControlerAPI;

    public function components() {
        $moduleService = ModuleService::fromRuntime($this);
        $modules = $moduleService->modules();
        $components = [];

        foreach ($modules as $module) {
            $components = array_merge($components, $moduleService->components($module));
        }

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