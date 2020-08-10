<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 22/02/2020
 * Time: 11:45
 */

namespace Plexus;


use Plexus\Error\PlexusError;
use Plexus\Exception\ModelException;
use Plexus\Utils\Text;

class ModelManager {

    /**
     * @var \PDO
     */
    protected $database;

    /**
     * @var string
     */
    protected $modelName;

    /**
     * @var string
     */
    protected $idColumn;

    /**
     * @var array
     */
    protected $structure;

    public function __construct(\PDO $database, $modelName, $idColumn='id') {
        $this->database = $database;
        $this->modelName = $modelName;
        $this->idColumn = $idColumn;
    }

    /**
     * @return string
     */
    public function getModelName() {
        return $this->modelName;
    }

    /**
     * @return array
     */
    public function getStructure() {
        $this->build_structure();
        return $this->structure;
    }

    /**
     *
     */
    protected function build_structure() {
        if ($this->structure === null) {
            try {
                $output = $this->database->query($this->_get_structure_query());
                $rawStructure = $output->fetchAll(\PDO::FETCH_ASSOC);
                $this->structure = $this->_parse_structure($rawStructure);
            } catch (\Throwable $exception) {
                throw new PlexusError(Text::format("Impossible de récupérer la structure du modèle {} ({})", $this->modelName, $this->_get_structure_query()), 0, $exception);
            }
        }
    }

    /**
     * @return string
     */
    protected function _get_structure_query() {
        // MySQL compatible query
        return "DESCRIBE $this->modelName";
    }

    /**
     * @param $rawStructure
     * @return array
     */
    protected function _parse_structure($rawStructure) {
        // MySQL compatible parsing
        $structure = [];
        foreach ($rawStructure as $column) {
            $structure[] = [
                'name' => $column['Field'],
                'type' => $this->_parse_column_type($column['Type'])
            ];
        }
        return $structure;
    }

    /**
     * @param $rawType
     * @return int
     */
    protected function _parse_column_type($rawType) {
        // MySQL compatible parsing
        $pattern = "/([a-zA-Z]+)\(?/";
        preg_match_all($pattern, $rawType, $matches, PREG_SET_ORDER, 0);
        $type = strtolower($matches[0][1]);
        switch ($type) {
            case 'time':
                return Model::$TIME;
            case 'date':
                return Model::$DATE;
            case 'datetime':
                return Model::$DATETIME;
            case 'timestamp':
            case 'year':
            case 'tinyint':
            case 'smallint';
            case 'mediumint';
            case 'int':
            case 'bigint':
                return Model::$INTEGER;
            case 'float':
            case 'double':
                return Model::$REAL;
            case 'text':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'varchar':
            case 'blob':
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'enum':
            default:
                return Model::$STRING;
        }
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder() {
        $qb = new QueryBuilder($this->database);
        $qb->from($this->modelName);
        return $qb;
    }

    /**
     * @param $id
     * @return null|Model
     * @throws ModelException
     */
    public function id($id) {
        return $this->get([$this->idColumn => $id]);
    }

    /**
     * @return ModelCollection
     * @throws ModelException
     */
    public function all() {
        return $this->select([]);
    }

    /**
     * @param ModelSelector|array ...$params
     * @return null|Model
     * @throws ModelException
     */
    public function get(...$params) {
        $models = $this->select(...$params);

        if ($models->length() > 0) {
            return $models->get(0);
        }
        return null;
    }

    /**
     * @param ModelSelector|array ...$params
     * @return ModelCollection
     * @throws ModelException
     */
    public function select(...$params) {
        if (count($params) == 1 && is_array($params[0])) {
            // Param is an array with value, we perform a simple WHERE .. [AND ...] query
            $sql = $this->build_select_request($params[0]);
            $request = $this->database->prepare($sql);
            if (!$request->execute($params[0])) {
                throw new ModelException(Text::format("Une erreur est survenue lors de la sélection du modèle '{}' ({}) ({})", $this->modelName, $sql, json_encode($params)));
            };

            $data = $request->fetchAll(\PDO::FETCH_ASSOC);

        } else {
            // Params are multiple ModelSelector, we build nd execute a QueryBuilder

            $qb = $this->getQueryBuilder();
            $qb->select('*');
            $dataMapping = [];

            foreach ($params as $param) {
                if ($param instanceof ModelSelector) {
                    switch ($param->getType()) {
                        case ModelSelector::WHERE:
                            $qb->where($param->getCondition());
                            $dataMapping = array_merge($dataMapping, $param->getMapping());
                            break;
                        case ModelSelector::ORDER:
                            $qb->order($param->getCondition(), $param->getOptions()['order']);
                            break;
                        case ModelSelector::GROUP:
                            $qb->group($param->getCondition());
                            break;
                        case ModelSelector::LIMIT:
                            $qb->limit($param->getCondition());
                            break;
                        case ModelSelector::OFFSET:
                            $qb->offset($param->getCondition());
                            break;
                    }
                }
            }

            try {
                $data = $qb->execute($dataMapping);
            } catch (\Throwable $exception) {
                throw new ModelException(Text::format("Une erreur est survenue lors de la sélection du modèle '{}' ({}) ({})", $this->modelName, $qb->query(), json_encode($dataMapping)), 0, $exception);
            }
        }

        $models = [];
        foreach ($data as $array) {
            $models[] = $this->_create($array);
        }
        return new ModelCollection($models);
    }

    /**
     * @param $model
     * @return string
     */
    protected function build_select_request($model) {
        // Make sure we already have the structure of the table
        $this->build_structure();
        $sql = "SELECT * FROM $this->modelName";
        if (count($model) > 0) {
            $sql .= " WHERE ";
            $acc = 0;
            foreach ($this->structure as $column) {
                if (array_key_exists($column['name'], $model)) {
                    $acc += 1;
                    if ($acc > 1) {
                        $sql .= " AND ";
                    }
                    $sql .= $column['name']." = :".$column['name'];
                }
            }
        }
        return $sql;
    }

    /**
     * @param Model $model
     * @param array $replacements
     * @throws ModelException
     */
    public function insert(Model $model, $replacements=[]) {
        $model_content = $model->getContent();

        // Let the database set the idColumn
        $replacements[$this->idColumn] = 'NULL';

        // Get the SQL
        $sql = $this->build_insert_request($replacements);

        $_model = [];
        foreach ($model_content as $key => $value) {
            if (!array_key_exists($key, $replacements)) {
                $_model[$key] = $value;
            }
        }

        $request = $this->database->prepare($sql);
        if (!$request->execute($_model)) {
            throw new ModelException(Text::format("Une erreur est survenue lors de l'insertion du modèle '{}' ({})", $this->modelName, $sql));
        };

        // Update the model from the database entry
        $_model = $this->id($this->database->lastInsertId($this->modelName));
        $model->update($_model->getContent());
    }

    /**
     * @param array $replacements
     * @return string
     */
    protected function build_insert_request($replacements=[]) {
        // Make sure we already have the structure of the table
        $this->build_structure();
        $sql = "INSERT INTO $this->modelName VALUES(";
        $acc = 0;
        foreach ($this->structure as $column) {
            $acc += 1;
            if ($acc > 1) {
                $sql .= ",";
            }
            if (array_key_exists($column['name'], $replacements)) {
                $sql .= $replacements[$column['name']];
            } else {
                $sql .= ":".$column['name'];
            }
        }
        $sql .= ")";

        return $sql;
    }

    /**
     * @param Model $model
     * @param array $replacements
     * @throws ModelException
     */
    public function update(Model $model, $replacements=[]) {

        $sql = $this->build_update_request($replacements);

        $model_content = $model->getContent();

        $_model = [];
        foreach ($model_content as $key => $value) {
            if (!array_key_exists($key, $replacements)) {
                $_model[$key] = $value;
            }
        }

        $request = $this->database->prepare($sql);
        if (!$request->execute($_model)) {
            throw new ModelException(Text::format("Une erreur est survenue lors de la mise à jour du modèle '{}' ({})", $this->modelName, $sql));
        }

        $_model = $this->id($model->get($this->idColumn));
        $model->update($_model->getContent());

    }

    /**
     * @param array $replacements
     * @return string
     */
    protected function build_update_request($replacements=[]) {
        // Make sure we already have the structure of the table
        $this->build_structure();
        $sql = "UPDATE $this->modelName SET ";
        $acc = 0;
        foreach ($this->structure as $column) {
            if ($column['name'] != 'id') {
                $acc += 1;
                if ($acc > 1) {
                    $sql .= ",";
                }
                if (array_key_exists($column['name'], $replacements)) {
                    $sql .= $column['name']." = ".$replacements[$column['name']];
                } else {
                    $sql .= $column['name']." = :".$column['name'];
                }

            }
        }
        $sql .= " WHERE $this->idColumn = :$this->idColumn";
        return $sql;
    }

    /**
     * @param Model $model
     * @throws ModelException
     */
    public function delete(Model $model) {

        $_model = ["id" => $model->get($this->idColumn)];

        $sql = "DELETE FROM $this->modelName WHERE $this->idColumn = :id";

        $request = $this->database->prepare($sql);
        if (!$request->execute($_model)) {
            throw new ModelException(Text::format("Une erreur est survenue lors de la suppression du modèle '{}' ({})", $this->modelName, $sql));
        };
    }

    /**
     * @param ModelSelector|array ...$params
     * @return ModelCollection
     * @throws ModelException
     */
    public function count(...$params) {
        if (count($params) == 1 && is_array($params[0])) {
            // Param is an array with value, we perform a simple WHERE .. [AND ...] query
            $sql = $this->build_count_request($params[0]);
            $request = $this->database->prepare($sql);
            if (!$request->execute($params[0])) {
                throw new ModelException(Text::format("Une erreur est survenue lors du comptage du modèle '{}' ({}) ({})", $this->modelName, $sql, json_encode($params)));
            };

            $data = $request->fetchAll(\PDO::FETCH_ASSOC);

        } else {
            // Params are multiple ModelSelector, we build nd execute a QueryBuilder

            $qb = $this->getQueryBuilder();
            $qb->select('COUNT(*) AS counter');
            $dataMapping = [];

            foreach ($params as $param) {
                if ($param instanceof ModelSelector) {
                    switch ($param->getType()) {
                        case ModelSelector::WHERE:
                            $qb->where($param->getCondition());
                            $dataMapping = array_merge($dataMapping, $param->getMapping());
                            break;
                        case ModelSelector::ORDER:
                            $qb->order($param->getCondition(), $param->getOptions()['order']);
                            break;
                        case ModelSelector::GROUP:
                            $qb->group($param->getCondition());
                            break;
                        case ModelSelector::LIMIT:
                            $qb->limit($param->getCondition());
                            break;
                        case ModelSelector::OFFSET:
                            $qb->offset($param->getCondition());
                            break;
                    }
                }
            }

            try {
                $data = $qb->execute($dataMapping);
            } catch (\Throwable $exception) {
                throw new ModelException(Text::format("Une erreur est survenue lors de la sélection du modèle '{}' ({}) ({})", $this->modelName, $qb->query(), json_encode($dataMapping)), 0, $exception);
            }
        }

        return intval($data[0]['counter']);
    }

    /**
     * @param $model
     * @return string
     */
    protected function build_count_request($model) {
        // Make sure we already have the structure of the table
        $this->build_structure();
        $sql = "SELECT COUNT(*) AS counter  FROM $this->modelName";
        if (count($model) > 0) {
            $sql .= " WHERE ";
            $acc = 0;
            foreach ($this->structure as $column) {
                if (array_key_exists($column['name'], $model)) {
                    $acc += 1;
                    if ($acc > 1) {
                        $sql .= " AND ";
                    }
                    $sql .= $column['name']." = :".$column['name'];
                }
            }
        }
        return $sql;
    }

    /**
     * @return Model
     * @throws Exception\ModelException
     */
    public function create() {
        return $this->_create();
    }

    /**
     * @param array $content
     * @return Model
     * @throws Exception\ModelException
     */
    protected function _create($content=[]) {
        return new Model($this, $content);
    }


}