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

use Inane\ServiceManager\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use Inane\Config\ConfigAware\{
    ConfigAwareAttribute,
    ConfigAwareTrait
};
use Inane\Stdlib\{
    Array\OptionsInterface,
    Options
};

use function call_user_func;

/**
 * The ServiceManager class is responsible for managing and providing access to services.
 * It allows for the registration of services along with their respective factory functions and
 * supports caching to optimize service retrieval.
 *
 * @version 0.1.0
 */
#[ConfigAwareAttribute(true)]
class ServiceManager implements ContainerInterface {
    /**
     * Trait ConfigAwareTrait
     *
     * Provides functionality for managing and accessing configuration settings.
     * Classes using this trait can store, retrieve, and work with configuration data.
     *
     * Implementing classes should ensure that the configuration is provided in an
     * appropriate format (e.g., array or object) and handle the edge cases for
     * undefined or missing configuration settings.
     */
    use ConfigAwareTrait;

    //#region Properties
    /**
     * @var OptionsInterface Services container
     */
    private OptionsInterface $services;

    /**
     * Retrieves the configuration object.
     *
     * This method returns the configuration object associated with the instance.
     *
     * @return OptionsInterface The configuration object.
     */
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

        foreach($services as $id => $function) {
            $sm->register($id, $function);
        }

        return $sm;
    }

    #region Service Management
    /**
     * Registers a new service with the specified name and factory.
     *
     * @param string   $id      The name of the service to register.
     * @param callable $factory A callable factory responsible for creating the service instance.
     *
     * @return void
     */
    public function register(string $id, callable $factory): void {
        $this->services->set($id, ['factory' => $factory, 'result' => null]);
    }

    /**
     * Checks if a service exists by its name.
     *
     * Determines whether a service with the specified name has been registered.
     *
     * @param string $id The name of the service to check for existence.
     *
     * @return bool True if the service exists, false otherwise.
     */
    public function has(string $id): bool {
        return $this->services->has($id);
    }

    /**
     * Builds a service by invoking its factory method.
     *
     * This always returns a new instance of the service.
     *
     * @param string $id The name of the service to build.
     *
     * @return mixed The result of invoking the service's factory method.
     *
     * @throws NotFoundException If the specified service is not found.
     */
    public function build(string $id): mixed {
        if (!$this->has($id)) {
            throw new NotFoundException("Service '{$id}' not found.");
        }

        return call_user_func($this->services->{$id}->factory, $this); // Pass the service manager for dependency resolution
    }

    /**
     * Retrieves a service by its name.
     *
     * A service is only built if it has not been cached. This method always returns the same instance of the service.
     *
     * @param string $id     The name of the service to retrieve.
     *
     * @return mixed The cached instance of the service.
     *
     * @throws NotFoundException If the specified service is not found.
     */
    public function get(string $id): mixed {
        if (!$this->has($id)) {
            throw new NotFoundException("Service '{$id}' not found.");
        }

        if ($rst = $this->services->{$id}->result) return $rst;

        $rst = $this->build($id);
        $this->services->{$id}->set('result', $rst);
        return $rst;
    }
    #endregion Service Management
}
