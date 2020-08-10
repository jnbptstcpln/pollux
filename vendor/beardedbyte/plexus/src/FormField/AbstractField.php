<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 22/02/2020
 * Time: 21:43
 */

namespace Plexus\FormField;


use Plexus\AbstractRuntime;
use Plexus\DataType\Collection;
use Plexus\FormValidator\AbstractValidator;
use Plexus\FormValidator\RequiredValidator;

abstract class AbstractField {

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var bool
     */
    protected $required;

    /**
     * @var bool
     */
    protected $disabled;

    /**
     * @var Collection
     */
    protected $classes;

    /**
     * @var Collection
     */
    protected $attributes;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    protected $displayedValue;

    /**
     * @var Collection
     */
    protected $validators;

    /**
     * @var Collection
     */
    protected $raw_validators;

    /**
     * @var Collection
     */
    protected $errors;

    /**
     * @var Collection
     */
    protected $settings;

    /**
     * @var bool
     */
    protected $validation_made;

    /**
     * @var AbstractRuntime
     */
    protected $runtime;

    public function __construct($id, $settings=[]) {
        $this->id = $id;
        $this->value = "";
        $this->validators = new Collection();
        $this->raw_validators = new Collection();
        $this->errors = new Collection();
        $this->settings = $this->buildSetting(new Collection($settings));

        $this->label = $this->settings->label;
        $this->name = ($this->settings->name !== null) ? $this->settings->name : $id;
        $this->required = (bool) $this->settings->required;
        $this->disabled = (bool) $this->settings->disabled;
        $this->classes = $this->settings->classes;
        $this->attributes = $this->settings->attributes;

        $validators = new Collection($this->settings->get('validators', []));
        $validators->each(function($i, AbstractValidator $validator) {
            $this->addValidator($validator);
        });

        $raw_validators = new Collection($this->settings->get('raw_validators', []));
        $raw_validators->each(function($i, AbstractValidator $validator) {
            $this->addRawValidator($validator);
        });
    }

    /**
     * @param Collection $settings
     * @return Collection
     */
    public function buildSetting(Collection $settings) {
        return new Collection([
            'name' => $settings->get('name', null),
            'label' => $settings->get('label'),
            'classes' => $settings->get('classes', new Collection()),
            'attributes' => $settings->get('attributes', new Collection()),
            'required' => $settings->get('required', false),
            'disabled' => $settings->get('disabled', false),
            'help_text' => $settings->get('help_text'),
            'error_display' => $settings->get('error_display', 'inline'),
            'validators' => $settings->get('validators', new Collection()),
            'raw_validators' => $settings->get('raw_validators', new Collection()),
            'has_error' => $settings->get('class_error', true)
        ]);
    }

    /**
     * @param bool $override
     * @return bool
     */
    public function validate($override=false) {
        if ($this->validation_made && !$override) {
            return $this->errors->length() == 0;
        }
        $this->validation_made = true;
        return $this->_validate();
    }

    /**
     * @return bool
     */
    protected function _validate() {

        $this->errors = new Collection();

        if ($this->required) {
            $validator = new RequiredValidator();
            if (!$validator->validate($this->getDisplayValue())) {
                $this->errors->push($validator->getError());
                return false;
            }
        }

        $this->validators->each(function($i, AbstractValidator $validator) {
            if (!$validator->validate($this->getDisplayValue())) {
                $this->errors->push($validator->getError());
                return !$validator->getStopValidation();
            }
            return true;
        });

        $this->raw_validators->each(function($i, AbstractValidator $validator) {
            if (!$validator->validate($this->getValue())) {
                $this->errors->push($validator->getError());
                return !$validator->getStopValidation();
            }
            return true;
        });

        return ($this->errors->length() == 0);
    }

    /**
     * @param array $options
     * @return string
     */
    public function render($options=[]) {
        return $this->_render($options);
    }

    /**
     * @param array $options
     * @return string
     */
    protected function _render($options=[]) {
        $options = new Collection($options);

        $output = "";
        if ($options->get('render_label', true)) {
            $output .= $this->renderLabel();
        }
        if ($this->settings->error_display === 'inline') {
            $output .= $this->renderInlineError();
        }
        $output .= $this->renderInput($options->get('render_value', true));
        $output .= $this->renderHelpText();
        return $output;
    }

    /**
     * @return string
     */
    protected function renderClasses() {
        $classes = "";
        $this->classes->each(function($i, $value) use (&$classes) {
            $classes .= (($i > 0) ? ' ' : '').htmlspecialchars($value);
        });
        if ($this->errors->length() > 0 && $this->settings->has_error) {
            $classes .= ((strlen($classes) > 0) ? ' ' : '').'has-error';
        }
        return $classes;
    }

    /**
     * @return string
     */
    public function renderAttributes() {
        $attributes = "";
        $this->attributes->each(function($name, $value) use (&$attributes) {
            if ($value === true) {
                $attributes .= sprintf("%s ", htmlspecialchars($name));
            } else {
                $attributes .= sprintf("%s=\"%s\" ", htmlspecialchars($name), htmlspecialchars($value));
            }
        });
        if ($this->settings->disabled) {
            $attributes .= "disabled ";
        }
        return $attributes;
    }

    /**
     * @return string
     */
    public function renderLabel() {
        if ($this->label !== null) {
            return sprintf("<label for='%s'>%s%s</label>",
                htmlspecialchars($this->id),
                htmlspecialchars($this->label),
                $this->settings->get('required') ? ' <b>*</b>' : ''
            );
        }
        return "";
    }

    /**
     * @return string
     */
    public function renderInlineError() {
        $errors = "";
        if ($this->errors->length() > 0) {
            if ($this->errors->length() == 1) {
                $error = $this->errors->get(0);
                if ($error->isInline()) {
                    $errors = sprintf("<p class=\"errors\">%s</p>", htmlspecialchars($this->errors->get(0)->getMessage()));
                }
            } else {
                $errors .= "<ul class=\"errors\">";
                $this->errors->each(function($i, FormError $error) use (&$errors) {
                    if ($error->isInline()) {
                        $errors .= sprintf("<li>%s</li>", htmlspecialchars($error->getMessage()));
                    }
                });
                $errors .= "</ul>";
            }
        }
        return $errors;
    }

    /**
     * @return string
     */
    public function renderHelpText() {
        $help_text = "";
        if ($this->settings->get('help_text', null) !== null) {
            $help_text = sprintf("<p class=\"text-help\">%s</p>", htmlspecialchars($this->settings->get('help_text', '')));
        }
        return $help_text;
    }

    /**
     * @return string
     */
    public function renderInput($withValue=true) {
        return "";
    }

    /**
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) {
        $this->value = $value;
        $this->displayedValue = $this->formatToDisplay($value);
        return $this;
    }

    /**
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLabel($value) {
        $this->label = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * @return bool
     */
    public function isDisabled() {
        return $this->disabled;
    }

    /**
     * @return Collection
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setAttribute($name, $value) {
        $this->attributes->set($name, $value);
        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayValue() {
        return $this->displayedValue;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDisplayValue($value) {
        $this->displayedValue = $value;
        $this->value = $this->formatFromDisplay($value);
        return $this;
    }

    /**
     * @param AbstractRuntime $runtime
     */
    public function setRuntime(AbstractRuntime $runtime) {
        $this->runtime = $runtime;
        $this->validators->each(function($i, AbstractValidator $validator) {
            $validator->setRuntime($this->runtime);
        });
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function formatToDisplay($value) {
        return $value;
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function  formatFromDisplay($value) {
        return $value;
    }

    /**
     * @param AbstractValidator $validator
     * @return $this
     */
    public function addValidator(AbstractValidator $validator) {
        $this->validators->push($validator);
        $validator->alterField($this);
        if ($this->runtime) {
            $validator->setRuntime($this->runtime);
        }
        return $this;
    }

    /**
     * @param AbstractValidator $validator
     * @return $this
     */
    public function addRawValidator(AbstractValidator $validator) {
        $this->raw_validators->push($validator);
        $validator->alterField($this);
        if ($this->runtime) {
            $validator->setRuntime($this->runtime);
        }
        return $this;
    }

}