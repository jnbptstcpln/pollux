<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:57
 */

namespace Plexus\FormField;


use Plexus\DataType\Collection;

class DateInput extends Input {

    /**
     * DateInput constructor.
     * @param $id
     * @param array $settings
     */
    public function __construct($id, $settings=[]) {
        parent::__construct($id, 'text', $settings);
    }

    public function buildSetting(Collection $settings) {
        $collection = parent::buildSetting($settings);
        $collection->set('date_format', $settings->get('date_format', 'd/m/Y'));
        return $collection;
    }

    /**
     * @param $value
     * @return string
     */
    public function formatFromDisplay($value) {
        if (strlen($value) > 0) {
            $date = date_create_from_format($this->settings->get('date_format'), $value);
            if ($date instanceof \DateTime) {
                return date('Y-m-d', $date->getTimestamp());
            } else {
                return date('Y-m-d');
            }
        } else {
            return "";
        }
    }

    /**
     * @param $value
     * @return string
     */
    public function formatToDisplay($value) {
        if (strlen($this->value) > 0) {
            return date($this->settings->get('date_format'), strtotime($this->value));
        }
        return '';
    }

}