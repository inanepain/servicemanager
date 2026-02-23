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

namespace Inane\ServiceManager\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * NotFoundException
 *
 * Required item not found.
 *
 * @version 0.1.0
 */
class NotFoundException extends \Inane\Stdlib\Exception\NotFoundException implements NotFoundExceptionInterface {
    protected $code = 760;
}
