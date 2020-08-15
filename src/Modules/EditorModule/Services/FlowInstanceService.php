<?php

namespace CPLN\Modules\EditorModule\Services;


use Plexus\AbstractRuntime;
use Plexus\Exception\PlexusException;
use Plexus\Model;
use Plexus\ModelSelector;
use Plexus\Service\AbstractService;
use Plexus\Utils\Randomizer;
use Plexus\Utils\Text;

class FlowInstanceService extends AbstractService {

    const STATE_QUEUED = "state_queued";
    const STATE_RUNNING = "state_running";
    const STATE_COMPLETED = "state_completed";
    const STATE_ERROR = "state_error";

    /**
     * @param AbstractRuntime $runtime
     * @param mixed ...$options
     * @return FlowInstanceService
     */
    public static function fromRuntime(AbstractRuntime $runtime, ...$options) {
        return parent::fromRuntime($runtime, $options); // TODO: Change the autogenerated stub
    }

    /**
     * @param $identifier
     * @return Model|null
     * @throws \Plexus\Exception\ModelException
     */
    public function get($identifier) {
        $instanceManager = $this->getModelManager("flow_instance");
        $instance = $instanceManager->get(['identifier' => $identifier]);
        if ($instance) {
            $instance->flow = FlowService::fromRuntime($this)->get($instance->flow_identifier);
            $instance->_environment = json_decode($instance->environment, true);
            $instance->_environment_initial = json_decode($instance->environment, true);

            if (strlen($instance->daemon_identifier) > 0) {
                $instance->daemon = $this->getModelManager("daemon")->get(['instance_id' => $instance->daemon_identifier]);
            }

        }
        return $instance;
    }

    /**
     * @param $instance_id
     * @return \Plexus\ModelCollection
     * @throws \Plexus\Exception\ModelException
     */
    public function for_daemon($instance_id) {
        $instanceManager = $this->getModelManager("flow_instance");
        $instances = $instanceManager->select(['daemon_identifier' => $instance_id]);

        $instances->each(function (Model $instance) {
            $flowManager = $this->getModelManager("flow");
            $instance->flow = $flowManager->get(["identifier" => $instance->flow_identifier]);
        });
        return $instances;

    }

    /**
     * Return the flow's instances in queue
     * @return \Plexus\ModelCollection
     * @throws \Plexus\Exception\ModelException
     */
    public function get_queue($limit=null) {
        $instanceManager = $this->getModelManager("flow_instance");

        if ($limit !== null) {
            $instances = $instanceManager->select(
                ModelSelector::where("state = :queued", ['queued' => self::STATE_QUEUED]),
                ModelSelector::order("created_on", "ASC"),
                ModelSelector::limit($limit)
            );
        } else {
            $instances = $instanceManager->select(
                ModelSelector::where("state = :queued", ['queued' => self::STATE_QUEUED]),
                ModelSelector::order("created_on", "ASC")
            );
        }


        $instances->each(function (Model $instance) {
            $flowManager = $this->getModelManager("flow");
            $instance->flow = $flowManager->get(["identifier" => $instance->flow_identifier]);
        });
        return $instances;
    }

    /**
     * @return \Plexus\ModelCollection
     * @throws \Plexus\Exception\ModelException
     */
    public function get_recent() {
        $instanceManager = $this->getModelManager("flow_instance");
        $instances = $instanceManager->select(
            ModelSelector::where("created_on > DATE_SUB(NOW(), INTERVAL 1 DAY) OR completed_on IS NULL"),
            ModelSelector::order("created_on", "DESC")
        );
        $instances->each(function (Model $instance) {
            $flowManager = $this->getModelManager("flow");
            $instance->flow = $flowManager->get(["identifier" => $instance->flow_identifier]);
        });
        return $instances;
    }

    /**
     * @param string $flowIdentifier
     * @param array|\stdClass $environment
     * @return string
     * @throws \Plexus\Exception\ModelException
     */
    public function create($flowIdentifier, $environment) {
        $instanceManager = $this->getModelManager("flow_instance");

        $identifier = sha1($flowIdentifier.time().Randomizer::string(10));
        while ($instanceManager->get(['identifier' => $identifier]) !== null) {
            $identifier = sha1($flowIdentifier.time().Randomizer::string(10));
        }

        $instance = $instanceManager->create();
        $instance->identifier = $identifier;
        $instance->state = self::STATE_QUEUED;
        $instance->flow_identifier = $flowIdentifier;
        $instance->environment_initial = json_encode($environment);

        $instanceManager->insert($instance, [
            "created_on" => "NOW()",
            "started_on" => "NULL",
            "completed_on" => "NULL",
        ]);

        // Log the creation
        FlowInstanceLogService::fromRuntime($this)->logMessage($identifier, "Création de l'instance d'exécution");

        return $identifier;
    }

    public function start($identifier, $daemon_identifier) {
        $instanceManager = $this->getModelManager("flow_instance");
        $instance = $instanceManager->get(['identifier' => $identifier]);
        if (!$instance) {
            throw new PlexusException(Text::format("Aucun instance ne correspond à l'identifiant {}", $identifier));
        }

        // Log the completion
        FlowInstanceLogService::fromRuntime($this)->logMessage($identifier, Text::format("Début de l'exécution du processus sur le daemon {}", $daemon_identifier));

        $instance->state = self::STATE_RUNNING;
        $instance->daemon_identifier = $daemon_identifier;
        $instance->environment = $instance->environment_initial;

        $instanceManager->update($instance, [
            "started_on" => "NOW()",
            "completed_on" => "NULL",
        ]);

        return $identifier;
    }

    public function complete($identifier, $environment) {
        $instanceManager = $this->getModelManager("flow_instance");
        $instance = $instanceManager->get(['identifier' => $identifier]);
        if (!$instance) {
            throw new PlexusException(Text::format("Aucun instance ne correspond à l'identifiant {}", $identifier));
        }

        // Log the completion
        FlowInstanceLogService::fromRuntime($this)->logMessage($identifier, "Exécution terminée avec succès");

        $instance->state = self::STATE_COMPLETED;
        $instance->environment = json_encode($environment);

        $instanceManager->update($instance, [
            "completed_on" => "NOW()"
        ]);
    }

    public function error($identifier, $message) {
        $instanceManager = $this->getModelManager("flow_instance");
        $instance = $instanceManager->get(['identifier' => $identifier]);
        if (!$instance) {
            throw new PlexusException(Text::format("Aucun instance ne correspond à l'identifiant {}", $identifier));
        }

        // Log the error
        FlowInstanceLogService::fromRuntime($this)->logMessage($identifier, $message);

        $instance->state = self::STATE_ERROR;

        $instanceManager->update($instance, [
            "completed_on" => "NOW()"
        ]);
    }




}