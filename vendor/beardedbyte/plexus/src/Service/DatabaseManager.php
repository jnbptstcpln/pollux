<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 19/02/2020
 * Time: 14:30
 */

namespace Plexus\Service;


use Plexus\AbstractRuntime;
use Plexus\Application;
use Plexus\DataType\Collection;
use Plexus\Error\ConfigError;
use Plexus\Error\PlexusError;
use Plexus\Model;
use Plexus\ModelManager;
use Plexus\ModelManagerOld;
use Plexus\QueryBuilder;
use Plexus\Utils\Text;

class DatabaseManager extends AbstractService {

    /**
     * @var \Plexus\Configuration
     */
    protected $configuration;

    /**
     * @var Collection
     */
    protected $databases;

    /**
     * @var Collection
     */
    protected $modelmanagers;

    public function __construct(Application $application) {
        parent::__construct($application);

        $this->databases = new Collection();
        $this->modelmanagers = new Collection();
        $this->configuration = $this->getGlobalConfiguration('databases');

        // Instanciate links to databases
        $this->configuration->each(function ($name, Collection $database) {

            if ($this->databases->isset($name)) {
                throw new ConfigError(Text::format("Une base de données porte déjà l'identifiant '{}'", $name));
            }

            $path = "";
            if ($database->isset("path")) {
                $path = $database->get("path");
            } else {
                $path = Text::format(
                    "{}:host={}:{};dbname={};charset=utf8",
                    $database->get("type"),
                    $database->get("host"),
                    $database->get("port", "3306"),
                    $database->get("database")
                );
            }
            try {
                $this->databases->set($name, new \PDO($path, $database->get('username'), $database->get('password')));
            } catch (\Throwable $exception) {
                throw new ConfigError(Text::format("Impossible de créer le lien vers la base de données '{}'", $name), 0, $exception);
            }

        });

    }

    /**
     * @param AbstractRuntime $runtime
     * @return self
     */
    public static function fromRuntime(AbstractRuntime $runtime, ...$options) {
        return parent::fromRuntime($runtime, ...$options);
    }

    /**
     * @param $database
     * @return QueryBuilder
     */
    public function getQueryBuilder($database) {
        if ($this->databases->isset($database)) {
            throw new PlexusError(Text::format("Aucune base de données n'a été trouvé pour l'identifiant '{}'", $database));
        }
        return new QueryBuilder($this->databases->get($database));
    }

    /**
     * @param $name
     * @param bool $override
     * @return ModelManager
     */
    public function getModelManager($name, $override=false) {

        if (!$this->modelmanagers->isset($name) || $override) {
            $parts = explode('.', $name);

            if (count($parts) == 1) {
                if ($this->databases->length() == 1) {
                    $dbName = $this->databases->keys()[0];
                    $modelName = $name;
                } else {
                    throw new PlexusError(Text::format("Vous devez spécifier la base de données à laquelle est liée le modèle '{}'", $name));
                }
            } else {
                $dbName = $parts[0];
                $modelName = $parts[1];
            }

            if (!$this->databases->isset($dbName)) {
                throw new PlexusError(Text::format("Aucune base de données nommée '{}' trouvée lors de la récupération du modèle '{}'", $dbName, $name));
            }
            $this->modelmanagers->set($name, new ModelManager($this->databases->get($dbName), $modelName));
        }

        return $this->modelmanagers->get($name);

    }
}