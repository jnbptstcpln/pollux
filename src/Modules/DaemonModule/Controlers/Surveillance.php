<?php


namespace CPLN\Modules\DaemonModule\Controlers;


use CPLN\Modules\DaemonModule\Services\DaemonLogService;
use CPLN\Modules\DaemonModule\Services\DaemonService;
use CPLN\Modules\EditorModule\Services\FlowInstanceLogService;
use CPLN\Modules\EditorModule\Services\FlowInstanceService;
use CPLN\Services\Label;
use Plexus\Controler;
use Plexus\ControlerAPI;
use Plexus\Model;
use Plexus\Service\Router;
use Plexus\Utils\Text;

class Surveillance extends Controler {

    use ControlerAPI;

    public function index() {
        $daemonService = DaemonService::fromRuntime($this);
        // First update daemons state
        $daemonService->update_daemon_table();
        $daemons = $daemonService->get_running_daemons();

        $this->render("@DaemonModule/surveillance/index.html.twig", [
            'daemons' => $daemons
        ]);
    }

    public function archives() {
        $daemonService = DaemonService::fromRuntime($this);
        // First update daemons state
        $daemonService->update_daemon_table();
        $daemons = $daemonService->get_dead_daemons();

        $this->render("@DaemonModule/surveillance/index.html.twig", [
            'daemons' => $daemons,
            'archive' => true
        ]);
    }

    public function details($instance_id) {

        $daemonService = DaemonService::fromRuntime($this);
        // First update daemons state
        $daemonService->update_daemon_table();

        $daemon = $daemonService->get($instance_id);

        if (!$daemon) {
            $this->halt(404);
        }

        $this->render("@DaemonModule/surveillance/details.html.twig", [
            'archive' => Router::fromRuntime($this)->getCurrentRoute()->getName() == "daemon-surveillance-archive-details",
            'daemon' => $daemon,
            'logs' => DaemonLogService::fromRuntime($this)->get($instance_id),
            'instances' => FlowInstanceService::fromRuntime($this)->for_daemon($instance_id)
        ]);
    }

    public function status($instance_id) {

        $daemonService = DaemonService::fromRuntime($this);
        // First update daemons state
        $daemonService->update_daemon_table();

        $daemon = $daemonService->get($instance_id);

        if (!$daemon) {
            $this->halt(404);
        }

        $instances = [];
        FlowInstanceService::fromRuntime($this)
            ->for_daemon($instance_id
            )->each(
                function (Model $instance) use (&$instances) {
                    $instances[] = [
                        'state' => Label::fromRuntime($this)->value_to_html($instance->state),
                        'name' => Text::format(
                            "<a href='{}'>{} : {}</a>",
                            $this->uriFor('flow-instance-details', $instance->identifier),
                            $instance->flow->name,
                            substr($instance->identifier, 0, 10)
                        ),
                        'started_since' => Label::fromRuntime($this)->since($instance->started_on)
                    ];
                }
            );


        $this->success([
            'continue' => $daemon->state !== DaemonService::STATE_DEAD,
            "daemon" => [
                "state" => Label::fromRuntime($this)->value_to_html($daemon->state)
            ],
            "logs" => DaemonLogService::fromRuntime($this)->get($instance_id, $this->paramGet("log_index", null))->toArray(),
            "instances" => $instances
        ]);
    }

    public function command_stop($instance_id) {
        $daemonService = DaemonService::fromRuntime($this);
        $daemon = $daemonService->send_command($instance_id, "stop");
        if (!$daemon) {
            $this->error(404, "Aucune instance ne correspond à votre requête.");
        }
        $this->success(null);
    }

    public function command_reload($instance_id) {
        $daemonService = DaemonService::fromRuntime($this);
        $daemon = $daemonService->send_command($instance_id, "reload");
        if (!$daemon) {
            $this->error(404, "Aucune instance ne correspond à votre requête.");
        }
        $this->success(null);
    }

}