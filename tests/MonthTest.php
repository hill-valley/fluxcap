<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Tests;

use HillValley\Fluxcap\Date;
use HillValley\Fluxcap\DateTime;
use HillValley\Fluxcap\Exception\InvalidPartException;
use HillValley\Fluxcap\Month;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \HillValley\Fluxcap\Base\IntlFormatter
 * @covers \HillValley\Fluxcap\Month
 */
final class MonthTest extends TestCase
{
    public function testConstruct(): void
    {
        $method = new \ReflectionMethod(Month::class, '__construct');

        self::assertTrue($method->isPrivate());
    }

    public function testGet(): void
    {
        $month = Month::get(Month::FEBRUARY);
        self::assertInstanceOf(Month::class, $month);

        $month2 = Month::get(Month::APRIL);
        self::assertInstanceOf(Month::class, $month2);

        self::assertNotSame($month, $month2);

        $month3 = Month::get(Month::FEBRUARY);
        self::assertInstanceOf(Month::class, $month3);

        self::assertSame($month, $month3);
    }

    /** @dataProvider dataGetInvalid */
    public function testGetInvalid(int $index): void
    {
        $this->expectException(InvalidPartException::class);

        Month::get($index);
    }

    public function dataGetInvalid(): iterable
    {
        return [
            [-1],
            [0],
            [13],
        ];
    }

    /** @dataProvider dataNamedConstructors */
    public function testNamedConstructors(int $expectedIndex, string $method): void
    {
        /** @var Month $month */
        $month = Month::$method();

        static::assertMonth($expectedIndex, $month);
    }

    public function dataNamedConstructors(): iterable
    {
        return [
            [Month::JANUARY, 'january'],
            [Month::FEBRUARY, 'february'],
            [Month::MARCH, 'march'],
            [Month::APRIL, 'april'],
            [Month::MAY, 'may'],
            [Month::JUNE, 'june'],
            [Month::JULY, 'july'],
            [Month::AUGUST, 'august'],
            [Month::SEPTEMBER, 'september'],
            [Month::OCTOBER, 'october'],
            [Month::NOVEMBER, 'november'],
            [Month::DECEMBER, 'december'],
        ];
    }

    /** @dataProvider dataCast */
    public function testCast(int $expectedIndex, $month): void
    {
        $month = Month::cast($month);
        static::assertMonth($expectedIndex, $month);
    }

    public function dataCast(): iterable
    {
        return [
            [Month::APRIL, 4],
            [Month::SEPTEMBER, Month::get(Month::SEPTEMBER)],
            [Month::MARCH, Date::fromString('2019-03-26')],
            [Month::MARCH, DateTime::fromString('2019-03-26 22:15:30')],
            [Month::MARCH, new \DateTime('2019-03-26 22:15:30')],
            [Month::MARCH, new \DateTimeImmutable('2019-03-26 22:15:30')],
        ];
    }

    public function testAll(): void
    {
        $all = Month::all();

        self::assertSame([
            1 => Month::get(Month::JANUARY),
            2 => Month::get(Month::FEBRUARY),
            3 => Month::get(Month::MARCH),
            4 => Month::get(Month::APRIL),
            5 => Month::get(Month::MAY),
            6 => Month::get(Month::JUNE),
            7 => Month::get(Month::JULY),
            8 => Month::get(Month::AUGUST),
            9 => Month::get(Month::SEPTEMBER),
            10 => Month::get(Month::OCTOBER),
            11 => Month::get(Month::NOVEMBER),
            12 => Month::get(Month::DECEMBER),
        ], $all);
    }

    public function testToString(): void
    {
        $month = Month::get(Month::OCTOBER);

        self::assertSame('October', (string) $month);
    }

    public function testGetIndex(): void
    {
        $month = Month::get(Month::OCTOBER);

        self::assertSame(10, $month->getIndex());
    }

    public function testGetName(): void
    {
        $month = Month::get(Month::OCTOBER);

        self::assertSame('October', $month->getName());
    }

    public function testGetAbbreviation(): void
    {
        $month = Month::get(Month::OCTOBER);

        self::assertSame('Oct', $month->getAbbreviation());
    }

    public function testGetLocalizedName(): void
    {
        $this->setLocale(LC_TIME, 'de_DE.UTF-8');

        $month = Month::get(Month::OCTOBER);

        self::assertSame('Oktober', $month->getLocalizedName());
    }

    public function testGetLocalizedAbbreviation(): void
    {
        $this->setLocale(LC_TIME, 'de_DE.UTF-8');

        $month = Month::get(Month::OCTOBER);

        self::assertSame('Okt', $month->getLocalizedAbbreviation());
    }

    public function testGetIntlName(): void
    {
        $month = Month::get(Month::OCTOBER);

        self::assertSame('Oktober', $month->getIntlName());
    }

    public function testGetIntlAbbreviation(): void
    {
        $month = Month::get(Month::OCTOBER);

        self::assertSame('Okt', $month->getIntlAbbreviation());
    }

    public function testEquals(): void
    {
        $month = Month::get(Month::APRIL);

        self::assertTrue($month->equals(Month::get(Month::APRIL)));
        self::assertTrue($month->equals(clone $month));
        self::assertFalse($month->equals(Month::get(Month::OCTOBER)));
    }

    /** @dataProvider dataDiffToPrev */
    public function testDiffToPrev(int $expected, int $month, int $prev): void
    {
        $month = Month::get($month);

        self::assertSame($expected, $month->diffToPrev(Month::get($prev)));
    }

    public function dataDiffToPrev(): iterable
    {
        return [
            [1, Month::APRIL, Month::MARCH],
            [11, Month::DECEMBER, Month::JANUARY],
            [6, Month::APRIL, Month::OCTOBER],
            [12, Month::APRIL, Month::APRIL],
        ];
    }

    /** @dataProvider dataDiffToNext */
    public function testDiffToNext(int $expected, int $month, int $next): void
    {
        $month = Month::get($month);

        self::assertSame($expected, $month->diffToNext(Month::get($next)));
    }

    public function dataDiffToNext(): iterable
    {
        return [
            [1, Month::MARCH, Month::APRIL],
            [11, Month::JANUARY, Month::DECEMBER],
            [5, Month::OCTOBER, Month::MARCH],
            [12, Month::APRIL, Month::APRIL],
        ];
    }

    public function testJsonSerialize(): void
    {
        $month = Month::get(Month::APRIL);

        self::assertJsonStringEqualsJsonString('4', json_encode($month));
    }

    public function testSetState(): void
    {
        $month = Month::get(Month::APRIL);

        $month2 = null;
        eval('$month2 = '.var_export($month, true).';');

        self::assertSame($month, $month2);
    }

    public function testSerialization(): void
    {
        $month = Month::get(Month::APRIL);

        static::assertMonth(Month::APRIL, unserialize(serialize($month)));
    }

    public function testDebugInfo(): void
    {
        $month = Month::get(Month::APRIL);

        $expected = [
            'index' => 4,
            'name' => 'April',
            'abbreviation' => 'Apr',
        ];

        self::assertSame($expected, $month->__debugInfo());
    }

    /**
     * @param Month $month
     */
    private static function assertMonth(int $expectedIndex, $month): void
    {
        self::assertInstanceOf(Month::class, $month);
        self::assertSame($expectedIndex, $month->getIndex());
    }
}
