<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Tests;

use HillValley\Fluxcap\Base\IntlFormatter;
use HillValley\Fluxcap\Date;
use HillValley\Fluxcap\DateTime;
use HillValley\Fluxcap\Weekday;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(IntlFormatter::class)]
#[CoversClass(Weekday::class)]
final class WeekdayTest extends TestCase
{
    #[DataProvider('dataCast')]
    public function testCast(Weekday $expected, $weekday): void
    {
        self::assertSame($expected, Weekday::cast($weekday));
    }

    public static function dataCast(): iterable
    {
        return [
            [Weekday::Wednesday, 3],
            [Weekday::Friday, Weekday::Friday],
            [Weekday::Tuesday, Date::fromString('2019-03-26')],
            [Weekday::Tuesday, DateTime::fromString('2019-03-26 22:15:30')],
            [Weekday::Tuesday, new \DateTime('2019-03-26 22:15:30')],
            [Weekday::Tuesday, new \DateTimeImmutable('2019-03-26 22:15:30')],
        ];
    }

    public function testAll(): void
    {
        $all = Weekday::all();

        self::assertSame([
            1 => Weekday::Monday,
            2 => Weekday::Tuesday,
            3 => Weekday::Wednesday,
            4 => Weekday::Thursday,
            5 => Weekday::Friday,
            6 => Weekday::Saturday,
            7 => Weekday::Sunday,
        ], $all);
    }

    public function testGetIndex(): void
    {
        self::assertSame(6, Weekday::Saturday->getIndex());
    }

    public function testGetName(): void
    {
        self::assertSame('Saturday', Weekday::Saturday->getName());
    }

    public function testGetAbbreviation(): void
    {
        self::assertSame('Sat', Weekday::Saturday->getAbbreviation());
    }

    public function testGetIntlName(): void
    {
        self::assertSame('Samstag', Weekday::Saturday->getIntlName());
    }

    public function testGetIntlAbbreviation(): void
    {
        self::assertSame('Sa', Weekday::Saturday->getIntlAbbreviation());
    }

    #[DataProvider('dataDiffToPrev')]
    public function testDiffToPrev(int $expected, Weekday $weekday, Weekday $prev): void
    {
        self::assertSame($expected, $weekday->diffToPrev($prev));
    }

    public static function dataDiffToPrev(): iterable
    {
        return [
            [1, Weekday::Wednesday, Weekday::Tuesday],
            [6, Weekday::Sunday, Weekday::Monday],
            [4, Weekday::Tuesday, Weekday::Friday],
            [7, Weekday::Tuesday, Weekday::Tuesday],
        ];
    }

    #[DataProvider('dataDiffToNext')]
    public function testDiffToNext(int $expected, Weekday $weekday, Weekday $next): void
    {
        self::assertSame($expected, $weekday->diffToNext($next));
    }

    public static function dataDiffToNext(): iterable
    {
        return [
            [1, Weekday::Tuesday, Weekday::Wednesday],
            [6, Weekday::Monday, Weekday::Sunday],
            [4, Weekday::Friday, Weekday::Tuesday],
            [7, Weekday::Tuesday, Weekday::Tuesday],
        ];
    }
}
