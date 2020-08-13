<?php


namespace CPLN\Modules\DaemonModule;


use Plexus\ControlerAPI;
use Plexus\Module;
use Plexus\Service\Renderer\TwigRenderer;

class DaemonModule extends Module {

    use ControlerAPI;

    public function middleware() {
        TwigRenderer::fromRuntime($this)->addGlobal("lefbarnav_active", "daemon");
    }

    public function onRun() {

    }

}