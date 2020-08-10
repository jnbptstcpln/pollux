<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:57
 */

namespace Plexus\FormField;


use Plexus\DataType\Collection;

class TextareaField extends AbstractField {

    public function __construct($id, $settings=[]) {
        parent::__construct($id, $settings);
    }

    /**
     * @param Collection $settings
     * @return Collection
     */
    public function buildSetting(Collection $settings) {
        $collection =  parent::buildSetting($settings);
        $collection->set('placeholder', $settings->get('placeholder'));
        return $collection;
    }

    /**
     * @return string
     */
    public function renderInput($withValue=true) {
        return sprintf('<textarea class="%s" id="%s" name="%s" %s %s>%s</textarea>',
            $this->renderClasses(),
            htmlspecialchars($this->id),
            !$this->settings->disabled ? htmlspecialchars($this->name) : '',
            $this->required ? 'required' : '',
            $this->renderAttributes(),
            ($withValue) ? htmlspecialchars($this->getDisplayValue()) : ''
        );
    }

}