<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:57
 */

namespace Plexus\FormField;


use Plexus\DataType\Collection;

class TimeInput extends Input {

    public function __construct($id, $settings=[]) {
        parent::__construct($id, 'text', $settings);
    }

    public function buildSetting(Collection $settings) {
        $collection = parent::buildSetting($settings);
        $collection->set('time_format', $settings->get('time_format', 'H:i'));
        return $collection;
    }

    /**
     * @param $value
     * @return string
     */
    public function formatFromDisplay($value) {
        if (strlen($value) > 0) {
            $date = date_create_from_format($this->settings->get('time_format'), $value);
            if ($date instanceof \DateTime) {
                return date('H:i:s', $date->getTimestamp());
            } else {
                return date('H:i:s');
            }
        } else {
            return "";
        }
    }

    /**
     * @param string $value
     * @return string
     */
    public function formatToDisplay($value) {
        if (strlen($this->value) > 0) {
            return date($this->settings->get('time_format'), strtotime($value));
        }
        return '';
    }

}