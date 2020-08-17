<?php

namespace CPLN\Modules\DaemonModule\Services;


use CPLN\Modules\EditorModule\Services\FlowInstanceService;
use Plexus\AbstractRuntime;
use Plexus\Model;
use Plexus\ModelSelector;
use Plexus\Service\AbstractService;
use Plexus\Utils\Randomizer;
use Plexus\Utils\Text;

class DaemonService extends AbstractService {

    const STATE_RUNNING = "state_running";
    const STATE_UNKNOWN = "state_unknown";
    const STATE_DEAD = "state_dead";

    /**
     * @param AbstractRuntime $runtime
     * @param mixed ...$options
     * @return DaemonService
     */
    public static function fromRuntime(AbstractRuntime $runtime, ...$options) {
        return parent::fromRuntime($runtime, $options); // TODO: Change the autogenerated stub
    }

    /**
     * @param $instance_id
     * @return Model|null
     * @throws \Plexus\Exception\ModelException
     */
    public function get($instance_id) {
        $daemonManager = $this->getModelManager("daemon");
        $daemon = $daemonManager->get(['instance_id' => $instance_id]);
        if ($daemon) {

        }
        return $daemon;
    }

    /**
     * @param $name
     * @param $machine
     * @param array $settings
     * @return mixed|string|null
     * @throws \Plexus\Exception\ModelException
     */
    public function start_daemon($name, $domain, $machine, $machine_name, $settings=[]) {
        $daemonManager = $this->getModelManager("daemon");
        $daemon = $daemonManager->create();

        $instance_id = sha1($machine.time().Randomizer::string(10));
        while ($daemonManager->get(['instance_id' => $instance_id]) !== null) {
            $instance_id = sha1($machine.time().Randomizer::string(10));
        }

        $daemon->instance_id = $instance_id;
        $daemon->state = self::STATE_RUNNING;
        $daemon->name = $name;
        $daemon->domain = $domain;
        $daemon->machine = $machine;
        $daemon->machine_name = $machine_name;
        $daemon->settings = json_encode($settings);

        $daemonManager->insert($daemon, [
            'last_update' => 'NOW()'
        ]);

        return $daemon->instance_id;
    }

    /**
     * @param $instance_id
     * @return \Plexus\Model|null
     * @throws \Plexus\Exception\ModelException
     */
    public function update_daemon($instance_id) {
        $daemonManager = $this->getModelManager("daemon");
        $daemon = $daemonManager->get(['instance_id' => $instance_id]);
        if ($daemon) {
            $daemonManager->update($daemon, [
                'last_update' => 'NOW()'
            ]);
        }
        return $daemon;
    }

    /**
     * @param $instance_id
     * @return \Plexus\Model|null
     * @throws \Plexus\Exception\ModelException
     */
    public function stop_daemon($instance_id) {
        $daemonManager = $this->getModelManager("daemon");
        $daemon = $daemonManager->get(['instance_id' => $instance_id]);
        if ($daemon) {
            $daemon->state = self::STATE_DEAD;
            $daemonManager->update($daemon, [
                'last_update' => 'NOW()'
            ]);
        }
        return $daemon;
    }

    /**
     * @throws \Plexus\Exception\ModelException
     */
    public function update_daemon_table() {
        $daemonManager = $this->getModelManager("daemon");
        $daemons = $daemonManager->select(
            ModelSelector::where("state != :state", ['state' => self::STATE_DEAD])
        );
        $daemons->each(function (Model $daemon) {
            if ($daemon->state == self::STATE_RUNNING && time() - strtotime($daemon->last_update) > 10) {
                $daemon->state = self::STATE_UNKNOWN;
                $daemon->getManager()->update($daemon);
            } elseif ($daemon->state == self::STATE_UNKNOWN && time() - strtotime($daemon->last_update) > 30) {
                $daemon->state = self::STATE_DEAD;
                $daemon->getManager()->update($daemon);
                $instanceService = FlowInstanceService::fromRuntime($this);
                $instanceService->for_daemon($daemon->instance_id)->each(function (Model $instance) use ($instanceService) {
                    $this->log("Kill instance".$instance->identifier);
                    if ($instance->state == FlowInstanceService::STATE_RUNNING) {
                        $instanceService->error($instance->identifier, Text::format("Arrêt du daemon {} hébergeant le processus", $instance->daemon_identifier));
                    }
                });
            }
        });
    }

    /**
     * @throws \Plexus\Exception\ModelException
     */
    public function get_running_daemons() {
        $daemonManager = $this->getModelManager("daemon");
        return $daemonManager->select(
            ModelSelector::where(
                "state = :running OR state = :unknown",
                ['running' => self::STATE_RUNNING, "unknown" => self::STATE_UNKNOWN]
            ),
            ModelSelector::order("name")
        );
    }

    /**
     * @throws \Plexus\Exception\ModelException
     */
    public function get_dead_daemons() {
        $daemonManager = $this->getModelManager("daemon");
        return $daemonManager->select(
            ModelSelector::where(
                "state = :dead",
                ['dead' => self::STATE_DEAD]
            ),
            ModelSelector::order("last_update", "desc")
        );
    }

    /**
     * @throws \Plexus\Exception\ModelException
     */
    public function get_by_domain($domain) {
        $daemonManager = $this->getModelManager("daemon");
        return $daemonManager->select(
            ModelSelector::where(
                "domain = :domain",
                ['domain' => $domain]
            ),
            ModelSelector::where(
                "state = :running OR state = :unknown",
                ['running' => self::STATE_RUNNING, "unknown" => self::STATE_UNKNOWN]
            )
        );
    }

}