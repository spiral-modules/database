<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Driver\MySQL;

/**
 * @group driver
 * @group driver-mysql
 */
class ReadonlyTest extends \Spiral\Database\Tests\ReadonlyTest
{
    public const DRIVER = 'mysql';
}
