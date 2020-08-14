<?php


namespace CPLN\Modules\LibraryModule\Controlers;


use CPLN\Modules\LibraryModule\Services\ModuleService;
use Plexus\Controler;
use Plexus\ControlerAPI;
use Plexus\Utils\Text;

class Components extends Controler {

    use ControlerAPI;

    /**
     * @throws \Plexus\Exception\HaltException
     */
    public function all() {
        $moduleService = ModuleService::fromRuntime($this);
        $modules = $moduleService->modules();
        $components = [];

        foreach ($modules as $module) {
            $components = array_merge($components, $moduleService->components($module));
        }

        $this->success($components);
    }

}