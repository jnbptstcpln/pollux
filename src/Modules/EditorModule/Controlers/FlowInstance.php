<?php

namespace CPLN\Modules\EditorModule\Controlers;


use CPLN\Modules\DaemonModule\Services\DaemonService;
use CPLN\Modules\EditorModule\Services\FlowInstanceLogService;
use CPLN\Modules\EditorModule\Services\FlowInstanceService;
use CPLN\Modules\EditorModule\Services\FlowService;
use CPLN\Services\Label;
use CPLN\Services\Library;
use Plexus\Controler;
use Plexus\ControlerAPI;
use Plexus\DataType\Collection;
use Plexus\Exception\ModelException;
use Plexus\Exception\PlexusException;
use Plexus\Form;
use Plexus\FormField\CSRFInput;
use Plexus\FormField\SelectField;
use Plexus\FormField\TextareaField;
use Plexus\FormField\TextInput;
use Plexus\FormValidator\LengthMaxValidator;
use Plexus\Model;
use Plexus\ModelSelector;
use Plexus\Utils\Randomizer;
use Plexus\Utils\Text;

class FlowInstance extends Controler {

    use ControlerAPI;

    public function all() {

        DaemonService::fromRuntime($this)->update_daemon_table();

        $instanceService = FlowInstanceService::fromRuntime($this);
        $instances = $instanceService->get_recent();

        $this->render("@EditorModule/flow_instance/all.html.twig", [
            "instances" => $instances
        ]);
    }

    public function create1() {

        $flowManager = $this->getModelManager("flow");
        $flows = [];
        $flowManager
            ->select(ModelSelector::order("name"))
            ->each(function (Model $flow) use (&$flows) {
                $flows[$flow->identifier] = $flow->name;
            })
        ;

        $form = new Form($this);
        $form
            ->setMethod("post")
            ->addField(new CSRFInput("flow_instance_creation1"))
            ->addField(new SelectField("flow_identifier", $flows, [
                'label' => "Démarrer une instance du processus..."
            ]))
        ;

        if ($this->method("post")) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {
                $flow_identifier = $form->getValueOf("flow_identifier");
                $this->redirect($this->uriFor("flow-instance-create2", $flow_identifier));
            }
        }

        $this->render("@EditorModule/flow_instance/create1.html.twig", [
            "form" => $form
        ]);
    }

    public function create2($flow_identifier) {

        $flow = FlowService::fromRuntime($this)->get($flow_identifier);
        if (!$flow) {
            $this->halt(404);
        }

        $form = new Form($this);
        $form
            ->setMethod("post")
            ->addField(new CSRFInput("flow_instance_creation2"))
            ->addField(new TextInput("domain", [
                'label' => "Domaine d'exécution de l'instance",
                'help_text' => "Permet de spécifier sur quels daemons peut être lancée cette instance",
                'required' => true,
                'validators' => [
                    new LengthMaxValidator(255)
                ]
            ]))
        ;

        foreach ($flow->settings['environment']['inputs'] as $input) {
            $form->addField(new TextInput("flow_input_".htmlentities($input), [
                'label' => $input,
                'validators' => [
                    new LengthMaxValidator(500)
                ]
            ]));
        }

        // Default form value
        $form->domain->setValue("default");

        if ($this->method("post")) {
            $form->fillWithArray($this->paramsPost());
            if ($form->validate()) {

                $domain = $form->getValueOf("domain");
                // Construction of the environment
                $environment = new \stdClass();
                foreach ($flow->settings['environment']['inputs'] as $input) {
                    $environment->$input = $form->getValueOf("flow_input_".htmlentities($input));
                }

                $instanceService = FlowInstanceService::fromRuntime($this);
                $instance_identifier = $instanceService->create($flow_identifier, $domain, $environment);

                $this->redirect($this->uriFor("flow-instance-details", $instance_identifier));
            }
        }

        $this->render("@EditorModule/flow_instance/create2.html.twig", [
            "form" => $form,
            "flow" => $flow
        ]);
    }

    public function details($identifier) {

        DaemonService::fromRuntime($this)->update_daemon_table();

        $instanceService = FlowInstanceService::fromRuntime($this);
        $instance = $instanceService->get($identifier);

        if (!$instance) {
            $this->halt(404);
        }

        if ($instance->state == FlowInstanceService::STATE_QUEUED) {
            $daemons = DaemonService::fromRuntime($this)->get_by_domain($instance->domain);
            if ($daemons->length() == 0) {
                $instance->warning = Text::format("Attention : aucun daemon n'est actuellement lancé dans le domaine <code>{}</code>", htmlentities($instance->domain));
            }
        }

        $this->render("@EditorModule/flow_instance/details.html.twig", [
            "instance" => $instance,
            "logs" => FlowInstanceLogService::fromRuntime($this)->get($identifier)
        ]);
    }

    public function status($identifier) {

        DaemonService::fromRuntime($this)->update_daemon_table();

        $instanceService = FlowInstanceService::fromRuntime($this);
        $instance = $instanceService->get($identifier);

        if (!$instance) {
            $this->halt(404);
        }

        $this->success([
            "instance" => [
                "state" => Label::fromRuntime($this)->value_to_html($instance->state),
                "daemon_identifier" =>  (strlen($instance->daemon_identifier) > 0) ? Text::format("<a target=\"_blank\" href=\"{}\">{}</a>", $this->uriFor('daemon-surveillance-details', $instance->daemon_identifier), htmlentities($instance->daemon->name)) : "<span class=\"text-muted\">L'exécution n'a pas encore démarré</span>",
                "environment" => (strlen($instance->daemon_identifier) > 0) ? $instance->environment : $instance->environment_initial
            ],
            "logs" => FlowInstanceLogService::fromRuntime($this)->get($identifier, $this->paramGet("log_index", null))->toArray()
        ]);
    }


}