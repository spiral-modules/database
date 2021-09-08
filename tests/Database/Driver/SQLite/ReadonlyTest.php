<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Driver\SQLite;

/**
 * @group driver
 * @group driver-sqlite
 */
class ReadonlyTest extends \Spiral\Database\Tests\ReadonlyTest
{
    public const DRIVER = 'sqlite';
}
