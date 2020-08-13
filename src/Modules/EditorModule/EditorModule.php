<?php

namespace CPLN\Modules\EditorModule;


use Plexus\Module;
use Plexus\Service\Renderer\TwigRenderer;

class EditorModule extends Module {

    public function middleware() {
        TwigRenderer::fromRuntime($this)->addGlobal("lefbarnav_active", "flow");
    }

}