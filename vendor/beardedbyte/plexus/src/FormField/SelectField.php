<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:57
 */

namespace Plexus\FormField;


use Plexus\DataType\Collection;
use Plexus\FormValidator\CollectionValidator;

class SelectField extends AbstractField {

    const OPTIONS_MODE_INDEX = 1;
    const OPTIONS_MODE_VALUE = 2;

    /**
     * @var Collection
     */
    protected $options;

    public function __construct($id, $options=[], $settings=[]) {
        parent::__construct($id, $settings);
        $this->setOptions($options);
    }

    /**
     * @param $options
     * @return $this
     */
    public function setOptions($options) {
        $this->options = new Collection();

        $options = new Collection($options);
        $_options = [];
        $options->each(function($i, $option) use(&$_options) {
            if (is_object($option)) {
                $this->options->push(new Collection(['value' => $option->value, 'label' => $option->label]));
                $_options[$option->value] = $option->label;
            } else {
                if ($this->settings->get("options_mode") == self::OPTIONS_MODE_INDEX) {
                    $this->options->push(new Collection(['value' => $i, 'label' => $option]));
                    $_options[$i] = $option;
                } else {
                    $this->options->push(new Collection(['value' => $option, 'label' => $option]));
                    $_options[$option] = $option;
                }
            }
        });

        $this->addValidator(new CollectionValidator($_options));

        return $this;
    }

    /**
     * @param Collection $settings
     * @return Collection
     */
    public function buildSetting(Collection $settings) {
        $collection =  parent::buildSetting($settings);
        $collection->set('placeholder', $settings->get('placeholder'));
        $collection->set('options_mode', $settings->get('options_mode', self::OPTIONS_MODE_INDEX));
        return $collection;
    }

    /**
     * @return Collection
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * @return string
     */
    public function renderInput($withValue=true) {
        $opening_tag = sprintf('<select class="%s" id="%s" name="%s" %s %s>',
            $this->renderClasses(),
            $this->id,
            !$this->settings->disabled ? htmlspecialchars($this->name) : '',
            $this->required ? 'required' : '',
            $this->renderAttributes()
        );

        $options = "";
        if ($this->settings->get('placeholder') !== null) {
            $options .= sprintf("<option disabled %s>%s</option>", ($this->getValue() == null || !$withValue) ? 'selected' : '', htmlspecialchars($this->settings->get('placeholder')));
        }
        $this->options->each(function($i, Collection $option) use (&$options, $withValue) {
            $options .= sprintf("<option value=\"%s\" %s>%s</option>", htmlspecialchars($option->value), ($option->value == $this->getValue() && $withValue) ? 'selected' : '', htmlspecialchars($option->label));
        });
        $closing_tag = "</select>";

        return $opening_tag.$options.$closing_tag;
    }

}