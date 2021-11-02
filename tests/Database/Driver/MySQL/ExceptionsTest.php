<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests\Driver\MySQL;

use Spiral\Database\Exception\StatementException\ConnectionException;

/**
 * @group driver
 * @group driver-mysql
 */
class ExceptionsTest extends \Spiral\Database\Tests\ExceptionsTest
{
    public const DRIVER = 'mysql';

    public function testPacketsOutOfOrderConsideredAsConnectionExceptionFromPHP74(): void
    {
        if (PHP_VERSION_ID < 70400) {
            $this->markTestSkipped('Expecting PHP version >=7.4. Skipped due to ' . PHP_VERSION);
        }

        // Prepare connection to generate "Packets out of order. Expected 1 received 0. Packet size=145"
        // at the next query response
        $this->database->query("SET SESSION wait_timeout=1")->fetch();
        sleep(1);

        try {
            $result = $this->database->query('SELECT version() AS version')->fetchAll();
            $this->assertNotEmpty($result[0]['version'] ?? '', 'Expected result from second query');
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf(ConnectionException::class, $e);
            return;
        }
    }
}
