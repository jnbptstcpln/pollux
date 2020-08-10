<?php

namespace CPLN\Modules\DaemonModule\Controlers;


class Link extends \Plexus\Controler {

    use \Plexus\ControlerAPI;

    public function start() {
        $name = $this->paramGet("name");
        $this->success([
            "instance_id" => rand(1000, 9999)
        ]);
    }

    public function fetch($instance_id) {
        $this->log($instance_id);
        $this->success([]);
    }

}