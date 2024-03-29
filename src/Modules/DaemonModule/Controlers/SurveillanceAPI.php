<?php

namespace CPLN\Modules\DaemonModule\Controlers;


use CPLN\Modules\DaemonModule\Services\DaemonLogService;
use CPLN\Modules\DaemonModule\Services\DaemonService;
use CPLN\Modules\EditorModule\Services\FlowInstanceService;
use Plexus\DataType\Collection;
use Plexus\Exception\ModelException;
use Plexus\ControlerAPI;
use Plexus\Model;


class SurveillanceAPI extends \Plexus\Controler {

    use ControlerAPI;

    public function start() {
        $name = $this->paramPost("name");
        $domain = $this->paramPost("domain", "default");
        $machine = $this->paramPost("machine");
        $machine_name = $this->paramPost("machine_name");
        $settings = json_decode($this->paramPost("settings"));

        $settings = $settings !== null ? $settings : new \stdClass();

        $daemonService = DaemonService::fromRuntime($this);
        try {
            $instance_id = $daemonService->start_daemon($name, $domain, $machine, $machine_name, $settings);
            $this->success([
                "instance_id" => $instance_id
            ]);
        } catch (ModelException $e) {
            $this->error(500, "Impossible de valider le démarrage du daemon : un problème est survenu lors de la mise à jour de la base de données.");
        }
    }

    public function update($instance_id) {
        $daemonService = DaemonService::fromRuntime($this);
        $daemon = $daemonService->update_daemon($instance_id);
        if (!$daemon) {
            $this->error(404, "Aucune instance ne correspond à votre requête.");
        }
    }

    public function fetch($instance_id) {
        $daemonService = DaemonService::fromRuntime($this);
        $daemon = $daemonService->update_daemon($instance_id);
        if (!$daemon) {
            $this->error(404, "Aucune instance ne correspond à votre requête.");
        }

        if ($daemon->state == DaemonService::STATE_DEAD) {
            $this->success([
                'state' => $daemon->state,
                'flows' => [],
                'queue' => []
            ]);
        }

        $flow_instances = -1;

        try {
            $status = json_decode($this->paramPost("status"), true);

            if ($status) {

                // Daemon logs
                $logManager = DaemonLogService::fromRuntime($this);
                $daemonLogs = isset($status["logs"]) ? $status["logs"] : [];
                foreach ($daemonLogs as $log) {
                    $logManager->logMessage($daemon->instance_id, $log["message"], $log["time"]);
                }

                $flow_instances = isset($status["flow_instances"]) ? intval($status["flow_instances"]) : -1;


            }
        } catch (\Throwable $e) {
            $this->log($e);
        }

        $settings = json_decode($daemon->settings, true);
        $settings = $settings !== null ? new Collection($settings) : new Collection([]);

        $flows = [];

        // Only send flow to execute if there is an execution slot available
        if ($flow_instances >= 0 && $flow_instances < $settings->get("concurrent_execution_limit", 1)) {
            // TODO : Optimize the distribution of flow between daemon
            $instanceManager = FlowInstanceService::fromRuntime($this);
            $queue = $instanceManager->get_queue($daemon->domain, 1);

            $queue->each(function (Model $instance) use (&$flows, $daemon) {
                $flows[] = [
                    'instance' => $instance->identifier,
                    'scheme' => json_decode($instance->flow->scheme),
                    'environment' => json_decode($instance->environment_initial)
                ];
                FlowInstanceService::fromRuntime($this)->start($instance->identifier, $daemon->instance_id);
            });
        }

        $this->success([
            'state' => $daemon->state,
            'flows' => $flows,
            'queue' => $daemonService->get_commands($daemon->instance_id)
        ]);
    }

    public function stop($instance_id) {
        $daemonService = DaemonService::fromRuntime($this);
        $daemon = $daemonService->stop_daemon($instance_id);
        if (!$daemon) {
            $this->error(404, "Aucune instance ne correspond à votre requête.");
        }

        try {
            $status = json_decode($this->paramPost("status"), true);

            if ($status) {

                // Daemon logs
                $logManager = DaemonLogService::fromRuntime($this);
                $daemonLogs = isset($status["logs"]) ? $status["logs"] : [];
                foreach ($daemonLogs as $log) {
                    $logManager->logMessage($daemon->instance_id, $log["message"], $log["time"]);
                }

            }
        } catch (\Throwable $e) {
            $this->log($e);
        }

        $this->success(null);
    }

}