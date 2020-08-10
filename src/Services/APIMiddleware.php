<?php


namespace CPLN\Services;


use Plexus\AbstractRuntime;
use Plexus\Application;
use Plexus\ControlerAPI;
use Plexus\Route;
use Plexus\Service\AbstractService;
use Plexus\Service\Router;
use Plexus\Utils\RegExp;

class APIMiddleware extends AbstractService {

    use ControlerAPI;

    public function __construct(Application $application) {
        parent::__construct($application);
        // Register a new middleware
        Router::fromRuntime($this)->addRoute(new Route("*", "*", "*", function() {
            if (RegExp::matches("/^\/api\/?.+/", $this->getRequest()->pathname())) {
                $key = $this->getRequest()->header("X-API-KEY");
                // TODO: Check the key is correct
                if (false) {
                    $this->error(403, "Please provide a valid key to use this API");
                }
            }
        }));
    }

    /**
     * @param AbstractRuntime $runtime
     * @param mixed ...$options
     * @return APIMiddleware
     */
    public static function fromRuntime(AbstractRuntime $runtime, ...$options) {
        return parent::fromRuntime($runtime, $options);
    }

}