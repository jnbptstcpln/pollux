<?php


namespace Plexus\DataType;


class CollectionIterator implements \Iterator {

    private $var = array();

    /**
     * CollectionIterator constructor.
     * @param Collection $collection
     */
    public function __construct(Collection $collection) {
        if (is_array($collection->getArray())) {
            $this->var = $collection->getArray();
        }
    }

    /**
     *
     */
    public function rewind() {
        reset($this->var);
    }

    /**
     * @return mixed
     */
    public function current() {
        return current($this->var);
    }

    /**
     * @return bool|float|int|string|null
     */
    public function key() {
        return key($this->var);
    }

    /**
     * @return mixed|void
     */
    public function next() {
        return next($this->var);
    }

    /**
     * @return bool
     */
    public function valid() {
        $key = key($this->var);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }

}