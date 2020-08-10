<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 01/08/2019
 * Time: 16:40
 */

namespace Plexus\FormValidator;


use Plexus\DataType\Collection;


class CollectionValidator extends AbstractValidator {

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * CollectionValidator constructor.
     * @param $collection
     */
    public function __construct($collection) {
        $this->collection = new Collection($collection);
        parent::__construct("Veuillez choisir une valeur pour ce champ", function($value) {
            return $this->collection->isset($value);
        });
    }
}