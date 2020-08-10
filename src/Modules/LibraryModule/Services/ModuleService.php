<?php


namespace CPLN\Modules\LibraryModule\Services;


use Plexus\AbstractRuntime;
use Plexus\Service\AbstractService;
use Plexus\Utils\Path;
use Plexus\Utils\Text;

class ModuleService extends AbstractService {

    public function onRun() {
        if (!is_dir(Path::build($this->application->root_path, "library"))) {
            mkdir(Path::build($this->application->root_path, "library"));
        }
        if (!is_dir(Path::build($this->application->root_path, "library", "modules"))) {
            mkdir(Path::build($this->application->root_path, "library", "modules"));
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
     * Check if the module_id exists
     * @param $module_id
     * @return bool
     */
    public function exists($module_id) {
        return file_exists($this->id_to_path($module_id));
    }

    /**
     * Return the SHA1 hash of the given module
     * @param $module_id
     * @return false|string
     */
    public function hash($module_id) {
        return sha1_file($this->id_to_path($module_id));
    }

    /**
     * Return the content of the given module
     * @param $module_id
     * @return false|string
     */
    public function content($module_id) {
        return file_get_contents($this->id_to_path($module_id));
    }

    /**
     * Convert module's id to its path
     * @param $module_id
     * @return bool|string
     */
    protected function id_to_path($module_id) {
        $parts = explode(".", $module_id);
        $fileName = array_pop($parts);
        $path = Path::build($this->application->root_path, "library", "modules");
        foreach ($parts as $part) {
            $path = Path::build($path, $part);
        }
        return Path::build($path, Text::format("{}.py", $fileName));
    }

}