<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Tests;

use HillValley\Fluxcap\Date;
use HillValley\Fluxcap\DateTime;
use HillValley\Fluxcap\Exception\InvalidPartException;
use HillValley\Fluxcap\Weekday;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \HillValley\Fluxcap\Base\IntlFormatter
 * @covers \HillValley\Fluxcap\Weekday
 */
final class WeekdayTest extends TestCase
{
    public function testConstruct(): void
    {
        $method = new \ReflectionMethod(Weekday::class, '__construct');

        self::assertTrue($method->isPrivate());
    }

    public function testGet(): void
    {
        $weekday = Weekday::get(Weekday::TUESDAY);
        self::assertInstanceOf(Weekday::class, $weekday);

        $weekday2 = Weekday::get(Weekday::FRIDAY);
        self::assertInstanceOf(Weekday::class, $weekday2);

        self::assertNotSame($weekday, $weekday2);

        $weekday3 = Weekday::get(Weekday::TUESDAY);
        self::assertInstanceOf(Weekday::class, $weekday3);

        self::assertSame($weekday, $weekday3);
    }

    /** @dataProvider dataGetInvalid */
    public function testGetInvalid(int $index): void
    {
        $this->expectException(InvalidPartException::class);

        Weekday::get($index);
    }

    public function dataGetInvalid(): iterable
    {
        return [
            [-1],
            [0],
            [8],
        ];
    }

    /** @dataProvider dataNamedConstructors */
    public function testNamedConstructors(int $expectedIndex, string $method): void
    {
        /** @var Weekday $weekday */
        $weekday = Weekday::$method();

        self::assertInstanceOf(Weekday::class, $weekday);
        self::assertSame($expectedIndex, $weekday->getIndex());
    }

    public function dataNamedConstructors(): iterable
    {
        return [
            [Weekday::MONDAY, 'monday'],
            [Weekday::TUESDAY, 'tuesday'],
            [Weekday::WEDNESDAY, 'wednesday'],
            [Weekday::THURSDAY, 'thursday'],
            [Weekday::FRIDAY, 'friday'],
            [Weekday::SATURDAY, 'saturday'],
            [Weekday::SUNDAY, 'sunday'],
        ];
    }

    /** @dataProvider dataCast */
    public function testCast(int $expected, $weekday): void
    {
        $weekday = Weekday::cast($weekday);
        self::assertInstanceOf(Weekday::class, $weekday);
        self::assertSame($expected, $weekday->getIndex());
    }

    public function dataCast(): iterable
    {
        return [
            [Weekday::WEDNESDAY, 3],
            [Weekday::FRIDAY, Weekday::get(Weekday::FRIDAY)],
            [Weekday::TUESDAY, Date::fromString('2019-03-26')],
            [Weekday::TUESDAY, DateTime::fromString('2019-03-26 22:15:30')],
            [Weekday::TUESDAY, new \DateTime('2019-03-26 22:15:30')],
            [Weekday::TUESDAY, new \DateTimeImmutable('2019-03-26 22:15:30')],
        ];
    }

    public function testAll(): void
    {
        $all = Weekday::all();

        self::assertSame([
            1 => Weekday::get(Weekday::MONDAY),
            2 => Weekday::get(Weekday::TUESDAY),
            3 => Weekday::get(Weekday::WEDNESDAY),
            4 => Weekday::get(Weekday::THURSDAY),
            5 => Weekday::get(Weekday::FRIDAY),
            6 => Weekday::get(Weekday::SATURDAY),
            7 => Weekday::get(Weekday::SUNDAY),
        ], $all);
    }

    public function testToString(): void
    {
        $weekday = Weekday::get(Weekday::SATURDAY);

        self::assertSame('Saturday', (string) $weekday);
    }

    public function testGetIndex(): void
    {
        $weekday = Weekday::get(Weekday::SATURDAY);

        self::assertSame(6, $weekday->getIndex());
    }

    public function testGetName(): void
    {
        $weekday = Weekday::get(Weekday::SATURDAY);

        self::assertSame('Saturday', $weekday->getName());
    }

    public function testGetAbbreviation(): void
    {
        $weekday = Weekday::get(Weekday::SATURDAY);

        self::assertSame('Sat', $weekday->getAbbreviation());
    }

    public function testGetIntlName(): void
    {
        $weekday = Weekday::get(Weekday::SATURDAY);

        self::assertSame('Samstag', $weekday->getIntlName());
    }

    public function testGetIntlAbbreviation(): void
    {
        $weekday = Weekday::get(Weekday::SATURDAY);

        self::assertSame('Sa', $weekday->getIntlAbbreviation());
    }

    public function testEquals(): void
    {
        $weekday = Weekday::get(Weekday::FRIDAY);

        self::assertTrue($weekday->equals(Weekday::get(Weekday::FRIDAY)));
        self::assertTrue($weekday->equals(clone $weekday));
        self::assertFalse($weekday->equals(Weekday::get(Weekday::TUESDAY)));
    }

    /** @dataProvider dataDiffToPrev */
    public function testDiffToPrev(int $expected, int $weekday, int $prev): void
    {
        $weekday = Weekday::get($weekday);

        self::assertSame($expected, $weekday->diffToPrev(Weekday::get($prev)));
    }

    public function dataDiffToPrev(): iterable
    {
        return [
            [1, Weekday::WEDNESDAY, Weekday::TUESDAY],
            [6, Weekday::SUNDAY, Weekday::MONDAY],
            [4, Weekday::TUESDAY, Weekday::FRIDAY],
            [7, Weekday::TUESDAY, Weekday::TUESDAY],
        ];
    }

    /** @dataProvider dataDiffToNext */
    public function testDiffToNext(int $expected, int $weekday, int $next): void
    {
        $weekday = Weekday::get($weekday);

        self::assertSame($expected, $weekday->diffToNext(Weekday::get($next)));
    }

    public function dataDiffToNext(): iterable
    {
        return [
            [1, Weekday::TUESDAY, Weekday::WEDNESDAY],
            [6, Weekday::MONDAY, Weekday::SUNDAY],
            [4, Weekday::FRIDAY, Weekday::TUESDAY],
            [7, Weekday::TUESDAY, Weekday::TUESDAY],
        ];
    }

    public function testJsonSerialize(): void
    {
        $weekday = Weekday::get(Weekday::FRIDAY);

        self::assertJsonStringEqualsJsonString('5', json_encode($weekday));
    }

    public function testSetState(): void
    {
        $weekday = Weekday::get(Weekday::FRIDAY);

        $weekday2 = null;
        eval('$weekday2 = '.var_export($weekday, true).';');

        self::assertSame($weekday, $weekday2);
    }

    public function testSerialization(): void
    {
        $weekday = Weekday::get(Weekday::FRIDAY);

        static::assertWeekday(Weekday::FRIDAY, unserialize(serialize($weekday)));
    }

    public function testDebugInfo(): void
    {
        $weekday = Weekday::get(Weekday::FRIDAY);

        $expected = [
            'index' => 5,
            'name' => 'Friday',
            'abbreviation' => 'Fri',
        ];

        self::assertSame($expected, $weekday->__debugInfo());
    }

    /**
     * @param Weekday $weekday
     */
    private static function assertWeekday(int $expectedIndex, $weekday): void
    {
        self::assertInstanceOf(Weekday::class, $weekday);
        self::assertSame($expectedIndex, $weekday->getIndex());
    }
}
