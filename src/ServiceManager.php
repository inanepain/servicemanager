<?php

/**
 * Inane: Services
 *
 * Service Manager.
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.5
 *
 * @author Philip Michael Raab<philip@cathedral.co.za>
 * @package inanepain\services
 * @category services
 *
 * @license UNLICENSE
 * @license https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types=1);

namespace Inane\ServiceManager;

use Inane\Config\ConfigAware\ConfigAwareAttribute;
use Inane\Config\ConfigAware\ConfigAwareTrait;
use Inane\Stdlib\Array\OptionsInterface;
use Inane\Stdlib\Exception\Exception;
use Inane\Stdlib\Options;

use function call_user_func;

/**
 * The ServiceManager class is responsible for managing and providing access to services.
 * It allows for the registration of services along with their respective factory functions and
 * supports caching to optimize service retrieval.
 *
 * @version 0.1.0
 */
#[ConfigAwareAttribute(true)]
class ServiceManager {
    use ConfigAwareTrait;

    //#region Properties
    private OptionsInterface $services;

    public function getConfig(): OptionsInterface {
        return $this->config;
    }

    //#endregion Properties

    /**
     * Creates and initializes a new service manager instance with the given services.
     *
     * @param OptionsInterface $services  A collection of services represented as a key-value mapping
     *                                    where each key is the service name and the value is its associated function.
     *
     * @return static A newly created service manager instance populated with the provided services.
     */
    public static function createServiceManager(OptionsInterface $services): static {
        $sm = new static();
        $sm->services = new Options();

        foreach($services as $name => $function) {
            $sm->register($name, $function);
        }

        return $sm;
    }

    /**
     * Registers a new service with the specified name and factory.
     *
     * @param string   $name    The name of the service to register.
     * @param callable $factory A callable factory responsible for creating the service instance.
     *
     * @return void
     */
    public function register(string $name, callable $factory): void {
        $this->services->set($name, ['factory' => $factory, 'result' => null]);
    }

    /**
     * Builds a service by invoking its factory method.
     *
     * This always returns a new instance of the service.
     *
     * @param string $name The name of the service to build.
     *
     * @return mixed The result of invoking the service's factory method.
     *
     * @throws Exception If the specified service is not found.
     */
    public function build(string $name) {
        if (!$this->services->has($name)) {
            throw new Exception("Service '{$name}' not found.");
        }

        return call_user_func($this->services->{$name}->factory, $this); // Pass the service manager for dependency resolution
    }

    /**
     * Retrieves a service by its name.
     *
     * A service is only built if it has not been cached. This method always returns the same instance of the service.
     *
     * @param string $name     The name of the service to retrieve.
     *
     * @return mixed The cached instance of the service.
     *
     * @throws Exception If the specified service is not found.
     */
    public function get(string $name) {
        if (!$this->services->has($name)) {
            throw new Exception("Service '{$name}' not found.");
        }

        if ($rst = $this->services->{$name}->result) return $rst;

        $rst = $this->build($name);
        $this->services->{$name}->set('result', $rst);
        return $rst;
    }
}
