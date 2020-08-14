<?php


namespace CPLN\Modules\LibraryModule\Controlers;


use CPLN\Modules\LibraryModule\Services\ModuleService;
use Plexus\Controler;
use Plexus\ControlerAPI;
use Plexus\Utils\Text;

class Modules extends Controler {

    use ControlerAPI;

    /**
     * @throws \Plexus\Exception\HaltException
     */
    public function search() {
        $module_id = $this->paramGet("module_id");
        $moduleService = ModuleService::fromRuntime($this);
        $modules = [];
        $this->log($module_id);
        if ($moduleService->exists($module_id)) {
            $modules[] = $module_id;
        }
        $this->success($modules);
    }

    /**
     * @param $module_id
     * @throws \Plexus\Exception\HaltException
     */
    public function hash($module_id) {
        $moduleService = ModuleService::fromRuntime($this);
        if ($moduleService->exists($module_id)) {
            $this->success([
                'hash' => $moduleService->hash($module_id)
            ]);
        }
        $this->error(404, Text::format("Aucun module correspondand à l'identifiant '{}'", $module_id));
    }

    /**
     * @param $module_id
     * @throws \Plexus\Exception\HaltException
     */
    public function download($module_id) {
        $moduleService = ModuleService::fromRuntime($this);
        if ($moduleService->exists($module_id)) {
            $this->success([
                'content' => $moduleService->content($module_id)
            ]);
        }
        $this->error(404, Text::format("Aucun module correspondand à l'identifiant '{}'", $module_id));
    }

}