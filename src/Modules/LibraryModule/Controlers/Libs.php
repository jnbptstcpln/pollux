<?php


namespace CPLN\Modules\LibraryModule\Controlers;


use CPLN\Modules\LibraryModule\Services\LibService;
use Plexus\Controler;
use Plexus\ControlerAPI;
use Plexus\Utils\Text;

class Libs extends Controler {

    use ControlerAPI;

    /**
     * @throws \Plexus\Exception\HaltException
     */
    public function search() {
        $lib_id = $this->paramGet("lib_id");
        $libService = LibService::fromRuntime($this);
        $libs = [];
        if ($libService->exists($lib_id)) {
            $libs[] = $lib_id;
        }
        $this->success($libs);
    }

    /**
     * @param $lib_id
     * @throws \Plexus\Exception\HaltException
     */
    public function hash($lib_id) {
        $libService = LibService::fromRuntime($this);
        if ($libService->exists($lib_id)) {
            $this->success([
                'hash' => $libService->hash($lib_id)
            ]);
        }
        $this->error(404, Text::format("Aucune bibliothèque correspondand à l'identifiant '{}'", $lib_id));
    }

    /**
     * @param $lib_id
     * @throws \Plexus\Exception\HaltException
     */
    public function download($lib_id) {
        $libService = LibService::fromRuntime($this);
        if ($libService->exists($lib_id)) {
            $this->success([
                'content' => $libService->content($lib_id)
            ]);
        }
        $this->error(404, Text::format("Aucun bibliothèque correspondand à l'identifiant '{}'", $lib_id));
    }

}