<?php


namespace CPLN\Modules\LibraryModule;


use CPLN\Modules\LibraryModule\Services\ModuleService;
use Plexus\ControlerAPI;
use Plexus\Module;
use Plexus\Utils\RegExp;

class LibraryModule extends Module {

    use ControlerAPI;

    public function onRun() {
        $moduleService = ModuleService::fromRuntime($this);
    }

}