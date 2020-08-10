<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 22/02/2020
 * Time: 20:54
 */

namespace Plexus;


use Plexus\DataType\Collection;
use Plexus\Error\PlexusError;
use Plexus\Exception\PlexusException;
use Plexus\FormField\AbstractField;
use Plexus\Utils\Text;

class Form extends AbstractRuntime implements Component {

    const POST = "post";
    const GET = "get";

    /**
     * @var Collection
     */
    protected $fieldsets;

    /**
     * @var Collection
     */
    protected $fields;

    /**
     * @var Collection
     */
    protected $fields_order;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var string
     */
    protected $enctype = "multipart/form-data";

    /**
     * @var Collection
     */
    protected $errors;


    /**
     * @var string
     */
    protected $submit_text;

    /**
     * @var bool
     */
    protected $validation_made = false;

    /**
     * @var bool|null
     */
    protected $validation_result;

    /**
     * @var AbstractRuntime
     */
    protected $runtime;


    public function __construct(AbstractRuntime $runtime) {
        parent::__construct($runtime->application);
        $this->method = strtolower((string) 'get');
        $this->action = strtolower((string) '');
        $this->fieldsets = new Collection();
        $this->fields = new Collection();
        $this->fields_order = new Collection();
        $this->errors = new Collection();
        $this->submit_text = "Envoyer";
        $this->runtime = $runtime;
    }

    /**
     * @param AbstractField $field
     * @param null $fieldset
     * @return $this
     */
    public function addField(AbstractField $field, $fieldset=null) {

        if ($this->fields->isset($field->getId())) {
            throw new PlexusError(Text::format("Un champs utilise déjà l'id '{}'", $field->getId()));
        }

        $field->setRuntime($this->runtime);

        if ($fieldset !== null) {
            if (!$this->fields->isset($fieldset)) {
                $this->fields->set($fieldset, new Collection());
            }
            $fieldset = $this->fields->get($fieldset);
            $fieldset->push($field->getId());
        } else {
            $this->fields_order->push($field->getId());
        }

        $this->fields->set($field->getId(), $field);

        return $this;

    }

    /**
     * @param bool $override
     * @return bool
     */
    public function validate($override=false) {
        if (!$this->validation_made || $override) {
            $valid = true;
            $this->fields->each(function($i, AbstractField $field) use (&$valid, $override) {
                if (!$field->validate($override)) {
                    $valid = false;
                };
            });
            $this->validation_result = $valid;
            $this->validation_made = true;
        }
        return ($this->validation_result && $this->errors->length() == 0);
    }

    /**
     * @param $name
     * @return AbstractField
     * @throws PlexusException
     */
    public function getField($name) {
        if (!$this->fields->isset($name)) {
            throw new PlexusException("Aucun champ nommé '{}' n'a été trouvé dans le formulaire.", $name);
        }
        return $this->fields->get($name);
    }

    /**
     * @return Collection
     */
    public function getFields() {
        $fields = new Collection();
        $this->fields_order->each(function($i, $field_id) use ($fields) {
            $fields->push($this->fields->get($field_id));
        });
        return $fields;
    }


    /**
     * @param $name
     * @return mixed|null|Collection
     * @throws PlexusException
     */
    public function getFieldset($name) {
        if (!$this->fieldsets->isset($name)) {
            throw new PlexusException("Aucune collection de champ nommée '{}' n'a été trouvée dans le formulaire.", $name);
        }
        return $this->fieldsets->get($name);
    }

    /**
     * @return Collection
     */
    public function getFieldsets() {
        $fieldsets = new Collection();
        $this->fieldsets->each(function($i, Collection $fieldset) use ($fieldsets) {
            $_fieldset = new Collection();
            $fieldset->each(function($i, $field_id) use ($_fieldset) {
                $_fieldset->push($this->fields->get($field_id));
            });
            $fieldsets->push($fieldset);
        });
        return $fieldsets;
    }

    /**
     * @param $message
     * @return $this
     */
    public function addError($message) {
        $this->errors->push(new FormError($message, FormError::$DISPLAY_GLOBAL));
        return $this;
    }

    /**
     * @return Collection
     */
    public function getErrors($include_inline_errors=false) {
        $errors = new Collection();
        $this->fields->each(function($i, AbstractField $field) use (&$errors, $include_inline_errors) {
            $field->getErrors()->each(function($j, FormError $error) use (&$errors, $include_inline_errors, $field) {
                if ($error->isGlobal()) {
                    $errors->push($error);
                } elseif ($include_inline_errors) {
                    $errors->push(new FormError(Text::format("{} : {}", $field->getLabel(), $error->getMessage())));
                }
            });
        });
        return $this->errors->mergeWith($errors);
    }

    /**
     *
     */
    public function fillWithRequest() {

        $array = $this->method === self::POST ? $_POST : $_GET;
        $collection = new Collection($array);

        $this->fields->each(function ($id, AbstractField $field) use ($collection) {
            if (!$field->isDisabled()) {
                $field->setDisplayValue($collection->get($field->getName()));
            }
        });
    }

    /**
     * @param $array
     */
    public function fillWithArray($array, $raw_values=false) {
        $array = new Collection($array);
        $this->fields->each(function ($id, AbstractField $field) use ($array, $raw_values) {
            if (!$field->isDisabled()) {
                if ($raw_values) {
                    $field->setValue($array->get($field->getName()));
                } else {
                    $field->setDisplayValue($array->get($field->getName()));
                }

            }
        });
    }

    /**
     * @param Model $model
     */
    public function fillWithModel(Model $model) {
        $this->fillWithArray($model->toArray(), true);
    }

    /**
     * @return string
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method) {
        $this->method = strtolower((string) $method);
        return $this;
    }

    /**
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setAction($action) {
        $this->action = strtolower((string) $action);
        return $this;
    }

    /**
     * @return string
     */
    public function getSubmitText() {
        return $this->submit_text;
    }

    /**
     * @param string $submit_text
     * @return $this
     */
    public function setSubmitText($submit_text) {
        $this->submit_text = $submit_text;
        return $this;
    }

    /**
     * @param bool $autofill
     * @return $this
     */
    public function setAutofill(bool $autofill) {
        $this->autofill = $autofill;
        return $this;
    }

    /**
     * @return string
     */
    public function render($options=[]) {
        $options = new Collection($options);

        $errors_html = "";
        $errors = $this->getErrors();
        if ($errors->length() > 0) {
            $errors_html .= '<div>';
            if ($errors->length() > 1) {
                $errors_html .= '<ul>';
                $errors->each(function($i, FormError $error) use (&$errors_html) {
                    $errors_html .= Text::format('<li>{}</li>', $error->getMessage());
                });
                $errors_html .= '</ul>';
            } else {
                $errors_html .= Text::format("<p>{}</p>",  $this->getErrors()->get(0)->getMessage());
            }
            $errors_html .= '</div>';
        }
        $fields_html = "";
        $this->fields->each(function($i, AbstractField $field) use (&$fields_html, $options) {
            $fields_html .= Text::format("<div>{}</div>", $field->render($options->get($field->getId(), [])));
        });
        return Text::format("<form method='{}' action='{}' enctype='{}'>{}{}{}</form>",
            htmlspecialchars($this->method),
            htmlspecialchars($this->action),
            htmlspecialchars($this->enctype),
            $errors_html,
            $fields_html,
            Text::format("<div><button type='submit'>{}</button></div>", $this->submit_text)
        );
    }

    /**
     * @return Collection
     */
    public function getValues() {
        $values = new Collection();
        $this->fields->each(function($i, AbstractField $field) use (&$values) {
            $values->set($field->getName(), $field->getValue());
        });
        return $values;
    }

    /**
     * @param $name
     * @return string
     * @throws PlexusException
     */
    public function getValueOf($name) {
        return $this->getField($name)->getValue();
    }

    /**
     * @return Collection
     */
    public function getDisplayValues() {
        $values = new Collection();
        $this->fields->each(function($i, AbstractField $field) use (&$values) {
            $values->set($field->getName(), $field->getDisplayValue());
        });
        return $values;
    }

    /**
     * @param $name
     * @return AbstractField
     * @throws \Exception
     */
    public function __get($name) {
        return $this->getField($name);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __isset($name) {
        return $this->fields->isset($name);
    }

    /**
     * @return array
     */
    public function export() {
        return [
            'method' => $this->method,
            'action' => $this->action,
            'enctype' => $this->enctype,
            'errors' => $this->getErrors()->toArray(),
            'fields' => $this->fields,
            'fields_order' => $this->fields_order
        ];
    }


}