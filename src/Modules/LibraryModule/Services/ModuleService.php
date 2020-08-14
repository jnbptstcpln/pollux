<?php


namespace CPLN\Modules\LibraryModule\Services;


use CPLN\Modules\LibraryModule\Structure\Component;
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
     * @return array
     */
    public function modules() {
        $modules = $this->_modules(Path::build($this->application->root_path, "library", "modules"));
        sort($modules);
        return $modules;
    }

    /**
     * @param $path
     * @return array
     */
    protected function _modules($path, $base="") {
        $modules = [];
        $items = array_diff(scandir($path), [".", ".."]);
        foreach ($items as $item) {
            if (is_dir(Path::build($path, $item))) {
                $modules = array_merge($this->_modules(Path::build($path, $item), $base.$item."."));
            } else {
                $name = preg_replace("/\.py$/", "", $base.$item);
                $modules[] = $name;
            }
        }
        return $modules;
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

    /**
     * @param $module_id
     */
    public function components($module_id) {
        $content = $this->content($module_id);

        preg_match_all("/class(.*\s)([[:blank:]].*\n)+/m", $content, $matches);

        $components = [];

        foreach ($matches[0] as $componentString) {

            if (!preg_match("/^class[[:blank:]]+(\w+)/m", $componentString, $matches_name)) {
                continue;
            }

            $id = Text::format("{}.{}", $module_id, $matches_name[1]);
            $description = "";
            $size = 1;

            if (preg_match("/:size (\d+)/m", $componentString, $matches_size)) {
                $size = intval($matches_size[1]);
            }

            if (preg_match("/:description (.*)/m", $componentString, $matches_description)) {
                $description = trim($matches_description[1]);
            }

            $component = new Component($id, $size, $description);

            preg_match_all("/:param ([^:]+):?([^:]+):?(.*)/m", $componentString, $matches_params, PREG_SET_ORDER);
            foreach ($matches_params as $param) {
                $component->addInput(trim($param[1]), trim($param[2]), trim($param[3]));
            }

            preg_match_all("/:return ([^:]+):?([^:]+):?(.*)/m", $componentString, $matches_return, PREG_SET_ORDER);
            foreach ($matches_return as $param) {
                $component->addOuput(trim($param[1]), trim($param[2]), trim($param[3]));
            }

            preg_match_all("/:setting ([^:]+):?([^:]+):?(.*)/m", $componentString, $matches_setting, PREG_SET_ORDER);
            foreach ($matches_setting as $param) {
                $component->addSetting(trim($param[1]), trim($param[2]), trim($param[3]));
            }

            preg_match_all("/:require (.*)/m", $componentString, $matches_requirement, PREG_SET_ORDER);
            foreach ($matches_requirement as $param) {
                $component->addRequirement(trim($param[1]));
            }

            $components[] = $component;
        }

        return $components;
    }

}