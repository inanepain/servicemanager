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
 * @author   Philip Michael Raab<philip@cathedral.co.za>
 * @package  inanepain\services
 * @category services
 *
 * @license  UNLICENSE
 * @license  https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types=1);

namespace Inane\ServiceManager\Tests;

use Inane\ServiceManager\Exception\NotFoundException;
use Inane\ServiceManager\ServiceManager;
use Inane\Stdlib\Exception\JsonException;
use Inane\Stdlib\Options;
use PHPUnit\Framework\TestCase;

/**
 * Helper classes used as service identifiers and to verify dependency resolution.
 */
class ValueHolder { public function __construct(public int $value) {} }

/**
 * Represents a class that requires a value holder upon instantiation.
 *
 * @param ValueHolder $holder An object instance of ValueHolder required for initialization.
 */
class NeedsValue { public function __construct(public ValueHolder $holder) {} }

/**
 * Test case for verifying the functionality of the ServiceManager.
 */
final class ServiceManagerTest extends TestCase {
    /**
     * Tests the creation of a service manager and verifies that services are properly registered.
     *
     * @return void
     *
     * @throws \Exception If the creation of the service manager fails or if the expected services are not registered.
     * @throws JsonException If an error occurs during JSON decoding for service configuration.
     */
    public function testCreateServiceManagerRegistersServices(): void {
        $services = new Options([
            ValueHolder::class => static fn (ServiceManager $sm) => new ValueHolder(42),
            NeedsValue::class  => static fn (ServiceManager $sm) => new NeedsValue($sm->get(ValueHolder::class)),
        ]);

        $sm = ServiceManager::createServiceManager($services);

        self::assertTrue($sm->has(ValueHolder::class));
        self::assertTrue($sm->has(NeedsValue::class));
    }

    /**
     * Tests that the service manager returns the same cached instance of a service and validates its properties.
     *
     * @return void
     *
     * @throws \Exception If the creation of the service manager fails or if the expected cached instance is not returned.
     * @throws NotFoundException If a required service is not found in the ServiceManager configuration.
     * @throws JsonException If an error occurs during JSON decoding for service configuration.
     */
    public function testGetCachesBuiltInstance(): void {
        $services = new Options([
            ValueHolder::class => static fn (ServiceManager $sm) => new ValueHolder(7),
        ]);

        $sm = ServiceManager::createServiceManager($services);

        $a = $sm->get(ValueHolder::class);
        $b = $sm->get(ValueHolder::class);

        // Same cached instance
        self::assertSame($a, $b);
        self::assertSame(7, $a->value);
    }

    /**
     * Tests that the build method of the ServiceManager always creates a new instance
     * of the specified service.
     *
     * This test ensures that when a service is built multiple times, distinct instances
     * are returned for each invocation, confirming non-singleton behavior.
     *
     * @return void
     *
     * @throws \RuntimeException If the service cannot be built due to a misconfiguration.
     * @throws NotFoundException If a required service is not found in the ServiceManager configuration.
     * @throws JsonException If an error occurs during JSON decoding for service configuration.
     */
    public function testBuildAlwaysCreatesNewInstance(): void {
        $services = new Options([
            ValueHolder::class => static fn (ServiceManager $sm) => new ValueHolder(9),
        ]);

        $sm = ServiceManager::createServiceManager($services);

        $a = $sm->build(ValueHolder::class);
        $b = $sm->build(ValueHolder::class);

        self::assertNotSame($a, $b);
        self::assertSame(9, $a->value);
        self::assertSame(9, $b->value);
    }

    /**
     * Tests that a factory receives the ServiceManager for resolving its dependencies.
     *
     * This test ensures that factories are correctly provided access to the ServiceManager,
     * allowing them to retrieve and resolve dependent services as part of the instantiation process.
     * Specifically, it validates that dependency resolution within factories works as expected.
     *
     * @return void
     *
     * @throws \RuntimeException If a requested service cannot be resolved due to a misconfiguration.
     * @throws NotFoundException If a required service is not found in the ServiceManager configuration.
     * @throws JsonException If an error occurs during JSON decoding for service configuration.
     */
    public function testFactoryReceivesServiceManagerForDependencyResolution(): void {
        $services = new Options([
            ValueHolder::class => static fn (ServiceManager $sm) => new ValueHolder(1234),
            NeedsValue::class  => static fn (ServiceManager $sm) => new NeedsValue($sm->get(ValueHolder::class)),
        ]);

        $sm = ServiceManager::createServiceManager($services);

        $needs = $sm->get(NeedsValue::class);
        self::assertSame(1234, $needs->holder->value);
    }

    /**
     * Tests that attempting to retrieve an unknown service from the service manager
     * throws a NotFoundException.
     *
     * @return void
     *
     * @throws NotFoundException If the requested service does not exist.
     */
    public function testGetThrowsNotFoundExceptionForUnknownService(): void {
        $sm = ServiceManager::createServiceManager(new Options());

        $this->expectException(NotFoundException::class);
        $sm->get(__NAMESPACE__ . '\\UnknownService');
    }
}
