<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Tests;

use HillValley\Fluxcap\Exception\InvalidStringException;
use HillValley\Fluxcap\TimeZone;
use PHPUnit\Framework\TestCase;

use function ini_get;

/**
 * @internal
 * @covers \HillValley\Fluxcap\TimeZone
 */
final class TimeZoneTest extends TestCase
{
    protected function tearDown(): void
    {
        date_default_timezone_set(ini_get('date.timezone'));
    }

    public function testConstruct(): void
    {
        $method = new \ReflectionMethod(TimeZone::class, '__construct');

        self::assertTrue($method->isPrivate());
    }

    public function testDefault(): void
    {
        $default = date_default_timezone_get();

        $timeZone = TimeZone::default();

        $this->assertTimeZone($default, $timeZone);
        self::assertSame($timeZone, TimeZone::default());

        $newDefault = 'America/Los_Angeles';
        date_default_timezone_set($newDefault);

        $this->assertTimeZone($newDefault, TimeZone::default());
    }

    public function testUtc(): void
    {
        $timeZone = TimeZone::utc();
        $this->assertTimeZone('UTC', $timeZone);
        self::assertSame($timeZone, TimeZone::utc());
    }

    /** @dataProvider dataFromString */
    public function testFromString(string $timeZone): void
    {
        $this->assertTimeZone($timeZone, TimeZone::fromString($timeZone));
    }

    public function dataFromString(): iterable
    {
        return [
            ['UTC'],
            ['Europe/Berlin'],
            ['+02:00'],
        ];
    }

    /** @dataProvider dataFromStringInvalid */
    public function testFromStringInvalid(string $expected, string $timeZone): void
    {
        $this->expectException(InvalidStringException::class);
        $this->expectExceptionMessage($expected);

        TimeZone::fromString($timeZone);
    }

    public function dataFromStringInvalid(): iterable
    {
        return [
            ['The time zone string can not be empty.', ''],
            ['Unknown time zone "foo".', 'foo'],
        ];
    }

    /** @dataProvider dataFromNative */
    public function testFromNative(string $expected, \DateTimeZone $timeZone): void
    {
        $this->assertTimeZone($expected, TimeZone::fromNative($timeZone));
    }

    public function dataFromNative(): iterable
    {
        return [
            ['UTC', new \DateTimeZone('UTC')],
            ['Europe/Berlin', new \DateTimeZone('Europe/Berlin')],
            ['+02:00', new \DateTimeZone('+02:00')],
        ];
    }

    /** @dataProvider dataCast */
    public function testCast(string $expected, $timeZone): void
    {
        $this->assertTimeZone($expected, TimeZone::cast($timeZone));
    }

    public function dataCast(): iterable
    {
        return [
            ['Europe/Paris', 'Europe/Paris'],
            ['Europe/Amsterdam', new \DateTimeZone('Europe/Amsterdam')],
            ['America/Los_Angeles', TimeZone::fromString('America/Los_Angeles')],
        ];
    }

    /** @dataProvider dataEquals */
    public function testEquals(bool $expected, string $timeZone1, string $timeZone2): void
    {
        $timeZone1 = TimeZone::fromString($timeZone1);
        $timeZone2 = TimeZone::fromString($timeZone2);

        self::assertSame($expected, $timeZone1->equals($timeZone2));
    }

    public function dataEquals(): iterable
    {
        return [
            [false, 'UTC', 'Europe/Berlin'],
            [false, 'UTC', 'GMT'],
            [false, 'UTC', '+00:00'],
            [false, 'Europe/Berlin', '+01:00'],
            [false, 'Europe/Berlin', 'CET'],
            [true, 'UTC', 'UTC'],
            [true, 'Europe/Berlin', 'Europe/Berlin'],
            [true, '+02:00', '+02:00'],
        ];
    }

    public function testIsDefault(): void
    {
        $timeZone1 = TimeZone::fromString('Europe/Berlin');
        $timeZone2 = TimeZone::fromString('America/Los_Angeles');

        self::assertTrue($timeZone1->isDefault());
        self::assertFalse($timeZone2->isDefault());

        date_default_timezone_set('America/Los_Angeles');

        self::assertFalse($timeZone1->isDefault());
        self::assertTrue($timeZone2->isDefault());
    }

    /** @dataProvider dataIsUtc */
    public function testIsUtc(bool $expected, string $timeZone): void
    {
        $timeZone = TimeZone::fromString($timeZone);

        self::assertSame($expected, $timeZone->isUtc());
    }

    public function dataIsUtc(): iterable
    {
        return [
            [true, 'UTC'],
            [false, 'GMT'],
            [false, 'Europe/London'],
            [false, '+01:00'],
        ];
    }

    public function testToNative(): void
    {
        $timeZone = TimeZone::fromString('America/Los_Angeles');
        $native = $timeZone->toNative();

        self::assertInstanceOf(\DateTimeZone::class, $native);
        self::assertSame('America/Los_Angeles', $native->getName());
    }

    public function testSetState(): void
    {
        $string = 'America/Los_Angeles';
        $timeZone = null;
        eval('$timeZone = '.var_export(TimeZone::fromString($string), true).';');

        self::assertTimeZone($string, $timeZone);
    }

    public function testJsonSerialize(): void
    {
        $timeZone = TimeZone::fromString('America/Los_Angeles');

        self::assertJsonStringEqualsJsonString('"America/Los_Angeles"', json_encode($timeZone));
    }

    public function testSerialization(): void
    {
        $string = 'America/Los_Angeles';

        self::assertTimeZone($string, unserialize(serialize(TimeZone::fromString($string))));
    }

    public function testDebugInfo(): void
    {
        $timeZone = TimeZone::fromString('America/Los_Angeles');

        $expected = [
            'name' => 'America/Los_Angeles',
        ];

        self::assertSame($expected, $timeZone->__debugInfo());
    }

    /**
     * @param TimeZone $timeZone
     */
    private function assertTimeZone(string $expected, $timeZone): void
    {
        self::assertInstanceOf(TimeZone::class, $timeZone);
        self::assertSame($expected, (string) $timeZone);
    }
}
