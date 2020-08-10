<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 19/02/2020
 * Time: 16:55
 */

namespace Plexus;


class ModelSelector {

    const WHERE = 0;
    const ORDER = 1;
    const GROUP = 2;
    const LIMIT = 3;
    const OFFSET = 4;

    /**
     * @var array
     */
    protected $type;

    /**
     * @var string
     */
    protected $condition;

    /**
     * @var array
     */
    protected $mapping;

    /**
     * @var array
     */
    protected $options;

    public function __construct($type, $condition, $mapping=[], $options=[]) {
        $this->type = $type;
        $this->condition = $condition;
        $this->mapping = $mapping;
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getCondition() {
        return $this->condition;
    }

    /**
     * @return array
     */
    public function getMapping() {
        return $this->mapping;
    }

    /**
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * @param $condition
     * @param array $mapping
     * @return ModelSelector
     */
    static function where($condition, $mapping=[]) {
        return new ModelSelector(self::WHERE, $condition, $mapping);
    }

    /**
     * @param string $condition
     * @param string $order
     * @return ModelSelector
     */
    static function order($condition, $order='ASC') {
        return new ModelSelector(self::ORDER, $condition, [], ['order' => $order]);
    }

    /**
     * @param $condition
     * @return ModelSelector
     */
    static function group($condition) {
        return new ModelSelector(self::GROUP, $condition);
    }

    /**
     * @param $limit
     * @return ModelSelector
     */
    static function limit($limit) {
        return new ModelSelector(self::LIMIT, $limit);
    }

    /**
     * @param $offset
     * @return ModelSelector
     */
    static function offset($offset) {
        return new ModelSelector(self::OFFSET, $offset);
    }

}