<?php

namespace CPLN\Modules\EditorModule\Controlers;


use CPLN\Modules\DaemonModule\Services\DaemonLogService;
use CPLN\Modules\EditorModule\Services\FlowInstanceLogService;
use CPLN\Modules\EditorModule\Services\FlowInstanceService;
use CPLN\Modules\EditorModule\Services\FlowService;
use CPLN\Services\Library;
use Plexus\Controler;
use Plexus\ControlerAPI;
use Plexus\Exception\ModelException;
use Plexus\Exception\PlexusException;
use Plexus\Form;
use Plexus\FormField\CSRFInput;
use Plexus\FormField\SelectField;
use Plexus\FormField\TextareaField;
use Plexus\Model;
use Plexus\ModelSelector;
use Plexus\Utils\Randomizer;
use Plexus\Utils\Text;

class FlowInstanceAPI extends Controler {

    use ControlerAPI;

    public function on_trigger($flow_identifier) {

        $flow = FlowService::fromRuntime($this)->get($flow_identifier);
        if (!$flow) {
            $this->error(404, Text::format("Aucun processus ne correspond à {}", $flow_identifier));
        }

        $data = json_decode(file_get_contents('php://input'));

        if ($data === null) {
            $this->error(400, 'Le format de votre requête est incorrect');
        }

        $environment = isset($data->environment) ? $data->environment : new \stdClass();

        $instanceService = FlowInstanceService::fromRuntime($this);
        $instance_identifier = $instanceService->create($flow_identifier, $environment);

        $this->success([
            "instance" => $instance_identifier
        ]);
    }

    public function on_status($identifier) {

        $instanceService = FlowInstanceService::fromRuntime($this);
        $instance = $instanceService->get($identifier);

        if (!$instance) {
            $this->error(404, Text::format("Aucune instance ne correspond à {}", $identifier));
        }

        $this->success([
            'state' => $instance->state,
            'environment' => json_decode($instance->environment)
        ]);
    }

    public function on_update($identifier) {

        $instanceService = FlowInstanceService::fromRuntime($this);
        $instance = $instanceService->get($identifier);

        if (!$instance) {
            $this->error(404, Text::format("Aucune instance ne correspond à {}", $identifier));
        }

        try {
            $status = json_decode($this->paramPost("status"), true);

            if ($status) {

                // Flow logs
                $logManager = FlowInstanceLogService::fromRuntime($this);
                $daemonLogs = isset($status["logs"]) ? $status["logs"] : [];
                foreach ($daemonLogs as $log) {
                    $logManager->logMessage($instance->identifier, $log["message"], $log["time"]);
                }

                // Flow environment
                $environment = isset($status["environment"]) ? $status["environment"] : [];
                $instance->environment = $environment;
                $instance->getManager()->update($instance);

            }
        } catch (\Throwable $e) {
            $this->log($e);
        }

        $this->success([
            'state' => $instance->state
        ]);

    }

    public function on_error($identifier) {

        $instanceService = FlowInstanceService::fromRuntime($this);
        $instance = $instanceService->get($identifier);

        if (!$instance) {
            $this->error(404, Text::format("Aucune instance ne correspond à {}", $identifier));
        }

        try {
            $status = json_decode($this->paramPost("status", null), true);

            if ($status) {

                // Flow logs
                $logManager = FlowInstanceLogService::fromRuntime($this);
                $daemonLogs = isset($status["logs"]) ? $status["logs"] : [];
                foreach ($daemonLogs as $log) {
                    $logManager->logMessage($instance->identifier, $log["message"], $log["time"]);
                }


            }
        } catch (\Throwable $e) {
            $this->log($e);
        }

        $instanceService->error($instance->identifier, $this->paramPost("message", "Une erreur a entrainé une fin d'exécution prématuré du processus"));

        $this->success(null);
    }

    public function on_complete($identifier) {

        $instanceService = FlowInstanceService::fromRuntime($this);
        $instance = $instanceService->get($identifier);

        if (!$instance) {
            $this->error(404, Text::format("Aucune instance ne correspond à {}", $identifier));
        }

        $environment = [];

        try {
            $status = json_decode($this->paramPost("status"), true);

            if ($status) {

                // Flow logs
                $logManager = FlowInstanceLogService::fromRuntime($this);
                $daemonLogs = isset($status["logs"]) ? $status["logs"] : [];
                foreach ($daemonLogs as $log) {
                    $logManager->logMessage($instance->identifier, $log["message"], $log["time"]);
                }

                // Flow environment
                $environment = isset($status["environment"]) ? json_decode($status["environment"]) : [];

            }
        } catch (\Throwable $e) {
            $this->log($e);
        }

        $instanceService->complete($instance->identifier, $environment);

        $this->success(null);
    }


}