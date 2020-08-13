<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 12/05/2020
 * Time: 00:11
 */

namespace CPLN\Services;


use Plexus\AbstractRuntime;
use Plexus\Application;
use Plexus\Error\PlexusError;
use Plexus\Exception\PlexusException;
use Plexus\Service\AbstractService;
use Plexus\Utils\Path;
use Plexus\Utils\Text;

class Library extends AbstractService {

    protected $libraryPath;

    /**
     * Library constructor.
     * @param Application $application
     * @param $libraryFolder
     * @throws \ReflectionException
     */
    public function __construct(Application $application, $libraryFolder) {

        parent::__construct($application);

        if (!is_string($libraryFolder)) {
            throw new PlexusError("Vous devez fournir le nom du dossier principal de la biliothèque");
        }

        $this->libraryPath = Path::build($this->getApplication()->root_path, $libraryFolder);
        // Deplor file structure if needed
        if (!file_exists($this->libraryPath)) {
            mkdir($this->libraryPath);
        }
    }

    /**
     * @param AbstractRuntime $runtime
     * @param mixed ...$options
     * @return Library
     */
    public static function fromRuntime(AbstractRuntime $runtime, ...$options) {
        return parent::fromRuntime($runtime, ...$options);
    }

    /**
     *
     */
    function onRun() {
    }

    /**
     * @param $identifier
     * @return array
     * @throws PlexusException
     */
    public function get($identifier, $default=null) {
        $file_path = $this->identifier_to_path($identifier);
        $data = $default;
        if (file_exists($file_path)) {
            $data = json_decode(file_get_contents($file_path), true);
        }
        return $data;
    }

    /**
     * @param $identifier
     * @param $data
     * @throws PlexusException
     */
    public function save($identifier, $data) {
        $file_path = $this->identifier_to_path($identifier);
        file_put_contents($file_path, json_encode($data));
    }

    /**
     * @param $identifier
     * @return string
     * @throws PlexusException
     */
    protected function identifier_to_path($identifier) {
        $path = $this->libraryPath;
        $parts = explode(":", $identifier);
        $fileName = array_pop($parts);
        foreach ($parts as $part) {
            if (!ctype_alnum($part)) {
                throw new PlexusException(Text::format("L'identifiant '{}' contient des caractères interdits.", $identifier));
            }
            $path = Path::build($path,$part);
            if (!file_exists($path)) {
                mkdir($path);
            }
        }

        if (!ctype_alnum($fileName)) {
            throw new PlexusException(Text::format("L'identifiant '{}' contient des caractères interdits.", $identifier));
        }
        $path = Path::build($path, Text::format("{}.json", $fileName));

        return $path;
    }

}