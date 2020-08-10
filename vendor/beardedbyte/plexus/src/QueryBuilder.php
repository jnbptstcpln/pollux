<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 23/09/2017
 * Time: 19:16
 */

namespace Plexus;


use Plexus\DataType\Collection;
use Plexus\Error\PlexusError;

class QueryBuilder
{

    /**
     * @var \PDO
     */
    protected $database;

    const STRING = 1;
    const INTEGER = 2;

    /**
     * @var array
     */
    public $select = [];

    /**
     * @var array
     */
    public $where = [];

    /**
     * @var array
     */
    public $join = [];

    /**
     * @var array
     */
    public $leftjoin = [];

    /**
     * @var array
     */
    public $rightjoin = [];

    /**
     * @var array
     */
    public $order = [];

    /**
     * @var array
     */
    public $group = [];

    /**
     * @var null|integer
     */
    public $limit = null;

    /**
     * @var null|integer
     */
    public $offset = null;

    /**
     * @var string
     */
    public $from;

    public function __construct($database=null) {
        $this->database = $database;
    }

    /**
     * @param $expression
     * @param string $alias
     *
     * @return $this
     */
    public function select($expression, $alias = '') {
        if (strlen($alias) > 0) {
            $expression = $expression.' AS '.$alias;
        }

        $this->select[] = $expression;

        return $this;
    }

    /**
     * @param $condition
     * @param string $operator
     *
     * @return $this
     */
    public function where($condition, $operator = 'AND') {
        if (count($this->where) > 0) {
            $this->where[] = $operator.' '.$condition;
        } else {
            $this->where[] = $condition;
        }

        return $this;
    }

    /**
     * @param $table_name
     * @param $glue
     * @return $this
     */
    public function join($table_name, $glue) {
        $this->join[] = ['table_name' => $table_name, 'glue' => $glue];

        return $this;
    }

    /**
     * @param $table_name
     * @param $glue
     * @return $this
     */
    public function leftjoin($table_name, $glue) {
        $this->leftjoin[] = ['table_name' => $table_name, 'glue' => $glue];

        return $this;
    }

    /**
     * @param $table_name
     * @param $glue
     * @return $this
     */
    public function rightjoin($table_name, $glue) {
        $this->rightjoin[] = ['table_name' => $table_name, 'glue' => $glue];

        return $this;
    }

    /**
     * @param $column
     * @param string $order
     *
     * @return $this
     */
    public function order($column, $order = 'ASC') {
        $this->order[] = $column.' '.$order;

        return $this;
    }

        /**
         * @param $column
         *
         * @return $this
         */
        public function group($column) {
            $this->group[] = $column;

            return $this;
        }

    /**
     * @param $limit
     *
     * @return $this
     */
    public function limit($limit) {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param $offset
     *
     * @return $this
     */
    public function offset($offset) {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param string $form
     */
    public function from($form) {
        $this->from = $form;
    }

    /**
     * @return string
     */
    public function query() {
        $query = 'SELECT';

        if (count($this->select) > 0) {
            $i = 0;
            foreach ($this->select as $select) {
                $i += 1;
                if ($i > 1) {
                    $query = $query.', '.$select;
                } else {
                    $query = $query.' '.$select;
                }

            }
        } else {
            $query = $query.' *';
        }

        $query = $query.' FROM '.$this->from;

        if (count($this->join) > 0) {
            foreach ($this->join as $join) {
                $query = $query.' JOIN '.$join['table_name'].' ON '.$join['glue'];
            }
        }

        if (count($this->leftjoin) > 0) {
            foreach ($this->leftjoin as $join) {
                $query = $query.' LEFT JOIN '.$join['table_name'].' ON '.$join['glue'];
            }
        }

        if (count($this->rightjoin) > 0) {
            foreach ($this->rightjoin as $join) {
                $query = $query.' RIGHT JOIN '.$join['table_name'].' ON '.$join['glue'];
            }
        }

        if (count($this->where) > 0) {
            $query = $query.' WHERE';

            foreach ($this->where as $where) {
                $query = $query.' '.$where;
            }
        }

        if (count($this->group) > 0) {
            $query = $query.' GROUP BY';

            $i = 0;
            foreach ($this->group as $column) {
                $i += 1;
                if ($i > 1) {
                    $query = $query.', '.$column;
                } else {
                    $query = $query.' '.$column;
                }

            }
        }

        if (count($this->order) > 0) {
            $query = $query.' ORDER BY';

            $i = 0;
            foreach ($this->order as $column) {
                $i += 1;
                if ($i > 1) {
                    $query = $query.', '.$column;
                } else {
                    $query = $query.' '.$column;
                }

            }
        }

        if ($this->limit != null) {
            $query = $query.' LIMIT '.$this->limit;
        }

        if ($this->offset != null) {
            $query = $query.' OFFSET '.$this->offset;
        }

        return $query;
    }

    /**
     * @param $params
     * @return array
     */
    public function execute($params) {
        if ($this->database === null) {
            throw new PlexusError("Aucune base de données a été associé à ce QueryBuilder");
        }
        $statement = $this->database->prepare($this->query());
        $statement->execute($params);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}