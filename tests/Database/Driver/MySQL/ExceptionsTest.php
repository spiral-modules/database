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

    protected function getConnectionId(): int
    {
        return (int) $this->database->query("SELECT CONNECTION_ID() AS id;")->fetchAll()[0]['id'] ?? 0;
    }

    public function testPacketsOutOfOrderConsideredAsConnectionException(): void
    {
        $connectionId = $this->getConnectionId();

        // Prepare connection to generate "Packets out of order. Expected 1 received 0. Packet size=145"
        // at the next query response
        $this->database->query("SET SESSION wait_timeout=1")->fetch();
        sleep(1);

        try {
            $newConnectionId = $this->getConnectionId();
            $this->assertNotEquals(0, $newConnectionId);
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf(ConnectionException::class, $e);
            return;
        }

        $this->assertNotSame($connectionId, $newConnectionId, 'Expected reconnect for database connection');
    }
}
