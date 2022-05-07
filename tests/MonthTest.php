<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Tests;

use HillValley\Fluxcap\Date;
use HillValley\Fluxcap\DateTime;
use HillValley\Fluxcap\Month;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \HillValley\Fluxcap\Base\IntlFormatter
 * @covers \HillValley\Fluxcap\Month
 */
final class MonthTest extends TestCase
{
    /** @dataProvider dataCast */
    public function testCast(Month $exptected, $month): void
    {
        self::assertSame($exptected, Month::cast($month));
    }

    public function dataCast(): iterable
    {
        return [
            [Month::April, 4],
            [Month::September, Month::September],
            [Month::March, Date::fromString('2019-03-26')],
            [Month::March, DateTime::fromString('2019-03-26 22:15:30')],
            [Month::March, new \DateTime('2019-03-26 22:15:30')],
            [Month::March, new \DateTimeImmutable('2019-03-26 22:15:30')],
        ];
    }

    public function testAll(): void
    {
        $all = Month::all();

        self::assertSame([
            1 => Month::January,
            2 => Month::February,
            3 => Month::March,
            4 => Month::April,
            5 => Month::May,
            6 => Month::June,
            7 => Month::July,
            8 => Month::August,
            9 => Month::September,
            10 => Month::October,
            11 => Month::November,
            12 => Month::December,
        ], $all);
    }

    public function testGetIndex(): void
    {
        self::assertSame(10, Month::October->getIndex());
    }

    public function testGetName(): void
    {
        self::assertSame('October', Month::October->getName());
    }

    public function testGetAbbreviation(): void
    {
        self::assertSame('Oct', Month::October->getAbbreviation());
    }

    public function testGetIntlName(): void
    {
        self::assertSame('Oktober', Month::October->getIntlName());
    }

    public function testGetIntlAbbreviation(): void
    {
        self::assertSame('Okt', Month::October->getIntlAbbreviation());
    }

    /** @dataProvider dataDiffToPrev */
    public function testDiffToPrev(int $expected, Month $month, Month $prev): void
    {
        self::assertSame($expected, $month->diffToPrev($prev));
    }

    public function dataDiffToPrev(): iterable
    {
        return [
            [1, Month::April, Month::March],
            [11, Month::December, Month::January],
            [6, Month::April, Month::October],
            [12, Month::April, Month::April],
        ];
    }

    /** @dataProvider dataDiffToNext */
    public function testDiffToNext(int $expected, Month $month, Month $next): void
    {
        self::assertSame($expected, $month->diffToNext($next));
    }

    public function dataDiffToNext(): iterable
    {
        return [
            [1, Month::March, Month::April],
            [11, Month::January, Month::December],
            [5, Month::October, Month::March],
            [12, Month::April, Month::April],
        ];
    }
}
