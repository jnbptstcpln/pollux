<?php
/**
 * Created by PhpStorm.
 * User: jeanbaptistecaplan
 * Date: 18/02/2020
 * Time: 22:22
 */

namespace Plexus\Service;


use Plexus\AbstractRuntime;
use Plexus\Application;
use Plexus\Error\PlexusError;
use Plexus\Utils\Text;

abstract class AbstractService extends AbstractRuntime {

    static protected $service_name = null;

    /**
     * AbstractService constructor.
     * @param Application $application
     * @throws \ReflectionException
     */
    public function __construct(Application $application) {
        parent::__construct($application);
        $className = get_called_class();
        $serviceName = $className::getServiceName();
        $services = $this->application->getServices();
        if ($services->isset($serviceName)) {
            throw new PlexusError(Text::format("A service already use the name '{}'", self::class));
        }
        $services->set($serviceName, $this);
    }

    /**
     * @param AbstractRuntime $runtime
     * @param mixed ...$options
     * @return self
     */
    static function fromRuntime(AbstractRuntime $runtime, ...$options) {
        $className = get_called_class();
        $serviceName = $className::getServiceName();
        $services = $runtime->getApplication()->getServices();
        if ($services->isset($serviceName)) {
            return $services->get($serviceName);
        }
        return new $className($runtime->getApplication(), ...$options);
    }

    public function onRun() {

    }

    /**
     * @return string
     */
    static protected function getServiceName() {
        return get_called_class();
    }
}