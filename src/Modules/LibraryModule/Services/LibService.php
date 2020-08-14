<?php


namespace CPLN\Modules\LibraryModule\Services;


use CPLN\Modules\LibraryModule\Structure\Component;
use Plexus\AbstractRuntime;
use Plexus\Service\AbstractService;
use Plexus\Utils\Path;
use Plexus\Utils\Text;

class LibService extends AbstractService {

    public function onRun() {
        if (!is_dir(Path::build($this->application->root_path, "library"))) {
            mkdir(Path::build($this->application->root_path, "library"));
        }
        if (!is_dir(Path::build($this->application->root_path, "library", "lib"))) {
            mkdir(Path::build($this->application->root_path, "library", "lib"));
        }
    }

    /**
     * @param AbstractRuntime $runtime
     * @param mixed ...$options
     * @return ModuleService
     */
    public static function fromRuntime(AbstractRuntime $runtime, ...$options) {
        return parent::fromRuntime($runtime, $options);
    }

    /**
     * @return array
     */
    public function libs() {
        $libs = $this->_libs(Path::build($this->application->root_path, "library", "lib"));
        sort($libs);
        return $libs;
    }

    /**
     * @param $path
     * @return array
     */
    protected function _libs($path, $base="") {
        $libs = [];
        $items = array_diff(scandir($path), [".", ".."]);
        foreach ($items as $item) {
            if (is_dir(Path::build($path, $item))) {
                $libs = array_merge($this->_libs(Path::build($path, $item), $base.$item."."));
            } else {
                $name = preg_replace("/\.py$/", "", $base.$item);
                $libs[] = $name;
            }
        }
        return $libs;
    }

    /**
     * Check if the lib_id exists
     * @param $lib_id
     * @return bool
     */
    public function exists($lib_id) {
        return file_exists($this->id_to_path($lib_id));
    }

    /**
     * Return the SHA1 hash of the given lib
     * @param $lib_id
     * @return false|string
     */
    public function hash($lib_id) {
        return sha1_file($this->id_to_path($lib_id));
    }

    /**
     * Return the content of the given lib
     * @param $lib_id
     * @return false|string
     */
    public function content($lib_id) {
        return file_get_contents($this->id_to_path($lib_id));
    }

    /**
     * Convert lib's id to its path
     * @param $lib_id
     * @return bool|string
     */
    protected function id_to_path($lib_id) {
        $parts = explode(".", $lib_id);
        $fileName = array_pop($parts);
        $path = Path::build($this->application->root_path, "library", "lib");
        foreach ($parts as $part) {
            $path = Path::build($path, $part);
        }
        return Path::build($path, Text::format("{}.py", $fileName));
    }

}