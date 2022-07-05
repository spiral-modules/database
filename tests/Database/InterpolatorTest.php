<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Database\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\Interpolator;
use stdClass;

class InterpolatorTest extends TestCase
{
    public function testInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE name = ? AND last_name = ? AND id IN(?, ?, ?) AND balance > ?';

        $parameters = [
            new Parameter('Anton?'),
            1,
            2,
            3,
            new Parameter(120),
            '?',
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            "SELECT * FROM table WHERE name = 'Anton?' AND last_name = '?' AND id IN(1, 2, 3) AND balance > 120",
            $interpolated
        );
    }

    public function testDatesInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name AND registered > :registered';

        $parameters = [
            ':name' => new Parameter('Anton'),
            ':registered' => new Parameter($date = new \DateTime('now')),
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = \'Anton\' AND registered > \''
            . $date->format(\DateTime::ATOM) . '\'',
            $interpolated
        );
    }

    public function testDateInterpolationWithDateTimeImmutable(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name AND registered > :registered';

        $parameters = [
            ':name' => new Parameter('Anton'),
            ':registered' => new Parameter($date = new \DateTimeImmutable('now')),
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = \'Anton\' AND registered > \''
            . $date->format(\DateTime::ATOM)
            . '\'',
            $interpolated
        );
    }

    public function testInterpolationUseNamedParameterTwice(): void
    {
        $query = 'SELECT :name as prefix, name FROM table WHERE name LIKE (CONCAT(:name, "%"))';

        $parameters = [
            ':name' => 'John',
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            "SELECT 'John' as prefix, name FROM table WHERE name LIKE (CONCAT('John', \"%\"))",
            $interpolated
        );
    }

    public function testInterpolateNamedParametersWhenFirstIsPrefixOfSecond(): void
    {
        $query = 'SELECT * FROM table WHERE parameter = :parameter AND param = :param';

        $parameters = [
            'param' => 'foo',
            'parameter' => 'bar',
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            "SELECT * FROM table WHERE parameter = 'bar' AND param = 'foo'",
            $interpolated
        );
    }

    public function testInterpolateParametersInStrings(): void
    {
        $query = 'SELECT \'?\', \'?\\\'?\', "?\\"?" as qq FROM table ' .
            'WHERE parameter = (":param", \':param\\\':param\', :param, ?)';

        $parameters = [
            'param' => 'foo',
            42,
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT \'?\', \'?\\\'?\', "?\\"?" as qq FROM table ' .
            'WHERE parameter = (":param", \':param\\\':param\', \'foo\', 42)',
            $interpolated
        );
    }

    public function testNestedInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE name = ? AND ' .
            "id IN(\"in dq ?\", 'in \n\n sq ?', ?) AND " .
            "balance > IN(\"in dq :p\", 'in sq :p', :p)";

        $parameters = [
            42,
            'foo',
            ':p' => 'bar',
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = 42 ' .
            "AND id IN(\"in dq ?\", 'in \n\n sq ?', 'foo') " .
            "AND balance > IN(\"in dq :p\", 'in sq :p', 'bar')",
            $interpolated
        );
    }

    public function testNamedParameterNotProvided(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name';

        $parameters = ['foo'];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = :name',
            $interpolated
        );
    }

    public function testUnnamedParameterNotProvided(): void
    {
        $query = 'SELECT * FROM table WHERE name = ? OR value > ?';

        $parameters = ['foo' => 'bar'];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = ? OR value > ?',
            $interpolated
        );
    }

    public function testStringableObjectInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name';

        $parameters = ['name' => new class () {
            public function __toString(): string
            {
                return 'foo';
            }
        }];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            "SELECT * FROM table WHERE name = 'foo'",
            $interpolated
        );
    }

    public function testNotStringableObjectInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name';

        $parameters = ['name' => new stdClass('foo')];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = [UNRESOLVED]',
            $interpolated
        );
    }

    public function testFloatValueInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE value > :value1 OR value < :value2';

        $parameters = ['value1' => 0.001, 'value2' => 0.0000001];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE value > 0.001000 OR value < 0.000000',
            $interpolated
        );
    }

    public function testNullBoolValueInterpolation(): void
    {
        $query = 'SELECT * FROM table WHERE value = :value1 OR value = :value2 OR value = ?';

        $parameters = ['value1' => true, 'value2' => false, null];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE value = TRUE OR value = FALSE OR value = NULL',
            $interpolated
        );
    }

    public function testNoParametersPassed(): void
    {
        $query = 'SELECT * FROM table WHERE name = :name';

        $parameters = [];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            'SELECT * FROM table WHERE name = :name',
            $interpolated
        );
    }

    public function testBackslashEscaping(): void
    {
        $query = 'SELECT :c26, :c0, :sq, :dq, :b, :bs, :n, :r, :t, :pc, :us, :pc_esc';

        $parameters = [
            'c26' => \chr(26),
            'c0' => \chr(0),
            'sq' => "'",
            'dq' => '"',
            'b' => \chr(8),
            'bs' => '\\',
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            'pc' => '%',
            'us' => '_',
            'pc_esc' => '\\%\\_',
        ];

        $interpolated = Interpolator::interpolate($query, $parameters);

        $this->assertSame(
            "SELECT '\\Z', '\\0', '\\'', '\"', '\\b', '\\\\', '\\n', '\\r', '\\t', '%', '_', '\\%\\_'",
            $interpolated
        );
    }
}
