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
class IndexesTest extends \Spiral\Database\Tests\IndexesTest
{
    public const DRIVER = 'mysql';

    public function testCreateOrderedIndex(): void
    {
        if (!$this->isOrderedIndexSupported()) {
            $this->expectExceptionMessageMatches('/column sorting is not supported$/');
        }

        parent::testCreateOrderedIndex();
    }

    public function testDropOrderedIndex(): void
    {
        if (!$this->isOrderedIndexSupported()) {
            $this->expectExceptionMessageMatches('/column sorting is not supported$/');
        }

        parent::testDropOrderedIndex();
    }

    protected function isOrderedIndexSupported(): bool
    {
        if (getenv('MYSQL') === '5.7') {
            return false;
        }

        return getenv('DB') !== 'mariadb';
    }
}
