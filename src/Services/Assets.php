<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 11/03/2020
 * Time: 13:23
 */

namespace CPLN\Services;


use Plexus\AbstractRuntime;
use Plexus\Application;
use Plexus\Exception\PlexusException;
use Plexus\Service\AbstractService;
use Plexus\Service\Renderer\AbstractRenderer;
use Plexus\Utils\Path;
use Plexus\Utils\RegExp;
use Plexus\Utils\Text;

class Assets extends AbstractService {

    protected $asset_src;

    protected $asset_export;

    protected $assets_uri;

    /**
     * @param AbstractRuntime $runtime
     * @param mixed ...$options
     * @return self
     */
    public static function fromRuntime(AbstractRuntime $runtime, ...$options) {
        return parent::fromRuntime($runtime, $options); // TODO: Change the autogenerated stub
    }

    /**
     * Assets constructor.
     * @param Application $application
     * @throws \ReflectionException
     */
    public function __construct(Application $application) {
        parent::__construct($application);

        $this->asset_src = Path::build($this->getApplication()->root_path, "src", "assets");
        $this->asset_export = Path::build($this->getApplication()->root_path, "public", "assets");
        $this->assets_uri = "/assets";
        $this->deploy();
    }

    public function onRun() {
        $renderer = AbstractRenderer::fromRuntime($this);
        $renderer->addFunction('asset_url', [$this, 'asset_url']);
    }

    /**
     * @param $identifier
     * @return string
     * @throws PlexusException
     */
    public function asset_url($identifier) {
        $path = $this->identifier_to_path($identifier);
        $this->prepare_src_path($path);
        $this->prepare_export_path($path);


        $asset_src = Path::build($this->asset_src, $path);
        $asset_export = Path::build($this->asset_export, $path);

        if (!file_exists($asset_src)) {
            touch($asset_src);
        }

        $src_hash = sha1_file($asset_src);
        $export_hash = sha1_file($asset_export);

        if ($src_hash != $export_hash) {
            if (!copy($asset_src, $asset_export)) {
                throw new PlexusException(Text::format("Impossible de copier le fichier ressource '{}' à l'emplacement '{}'", $asset_src, $asset_export));
            }
        }

        return Text::format("{}{}/{}?hash={}", $this->getApplication()->base_url(), $this->assets_uri, $path, $src_hash);

    }

    /**
     * @param $identifier
     * @return bool|string
     * @throws PlexusException
     */
    private function identifier_to_path($identifier) {
        $identifier_parts = explode(':', $identifier);
        $filename = array_pop($identifier_parts);

        $path = "";
        foreach ($identifier_parts as $item) {
            $pattern = "/[\w]/";
            if (!RegExp::matches($pattern, $item)) {
                throw new PlexusException(Text::format("{} n'est pas un nom de bundle d'asset valide", $identifier));
            }
            $path = Path::build($path, $item);
        }
        return Path::build($path, $filename);
    }

    /**
     * @param $path
     * @throws PlexusException
     */
    private function prepare_export_path($path) {
        $path =  join(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $path), 0, -1));
        if (!file_exists(Path::build($this->asset_export, $path))) {
            $temp_path = $this->asset_export;
            foreach (explode(DIRECTORY_SEPARATOR, $path) as $name) {
                $temp_path = Path::build($temp_path, $name);
                if (!file_exists($temp_path)) {
                    if (!mkdir($temp_path)) {
                        throw new PlexusException(Text::format("Impossible de créer le dossier '{}' lors du chargements des ressources", $temp_path));
                    }
                }
            }
        }
    }

    /**
     * @param $path
     * @throws PlexusException
     */
    private function prepare_src_path($path) {
        $path =  join(DIRECTORY_SEPARATOR, array_slice(explode(DIRECTORY_SEPARATOR, $path), 0, -1));
        if (!file_exists(Path::build($this->asset_src, $path))) {
            $temp_path = $this->asset_src;
            foreach (explode(DIRECTORY_SEPARATOR, $path) as $name) {
                $temp_path = Path::build($temp_path, $name);
                if (!file_exists($temp_path)) {
                    if (!mkdir($temp_path)) {
                        throw new PlexusException(Text::format("Impossible de créer le dossier '{}' lors du chargements des ressources", $temp_path));
                    }
                }
            }
        }
    }

    protected function deploy() {
        $this->_mkdir($this->asset_src);
        $this->_mkdir($this->asset_export);
    }

    private function _mkdir($dir) {
        if (!is_dir($dir)) {
            mkdir($dir);
        }
    }

}