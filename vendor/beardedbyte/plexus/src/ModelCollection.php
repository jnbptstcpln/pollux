<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 04/08/2019
 * Time: 00:19
 */

namespace Plexus;


use Plexus\DataType\Collection;
use Plexus\DataType\CollectionIterator;

class ModelCollection {

    /**
     * @var array
     */
    protected $models;

    /**
     * ModelCollection constructor.
     * @param array $models
     */
    public function __construct($models) {
        $this->models = $models;
    }

    /**
     * @param $index
     * @return Model|null
     */
    public function get($index) {
        return (isset($this->models[$index])) ? $this->models[$index] : null;
    }

    /**
     * @return int
     */
    public function length() {
        return count($this->models);
    }

    /**
     * @param callable $function
     */
    public function each(callable $function) {
        foreach ($this->models as $model) {
            $function($model);
        }
    }

    /**
     * @param null|array $fields
     * @return array
     */
    public function getContent($fields=null) {
        $models = [];
        foreach ($this->models as $model) {
            $models[] = $model->getContent($fields);
        }
        return $models;
    }

    /**
     * @param null|array $fields
     * @return array
     */
    public function toArray($fields=null) {
        $models = [];
        foreach ($this->models as $model) {
            $models[] = $model->toArray($fields);
        }
        return $models;
    }
}