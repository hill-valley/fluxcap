<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Tests;

use HillValley\Fluxcap\Base\IntlFormatter;
use HillValley\Fluxcap\Date;
use HillValley\Fluxcap\DateTime;
use HillValley\Fluxcap\Duration;
use HillValley\Fluxcap\Exception\FormatMismatchException;
use HillValley\Fluxcap\Exception\InvalidPartException;
use HillValley\Fluxcap\Exception\InvalidStringException;
use HillValley\Fluxcap\Month;
use HillValley\Fluxcap\TimeZone;
use HillValley\Fluxcap\Weekday;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(IntlFormatter::class)]
#[CoversClass(Date::class)]
final class DateTest extends TestCase
{
    public function testConstruct(): void
    {
        $method = new \ReflectionMethod(Date::class, '__construct');

        self::assertTrue($method->isPrivate());
    }

    #[DataProvider('dataTimeZones')]
    public function testToday(string $expectedTimezone, ...$parameters): void
    {
        $expected = new \DateTimeImmutable('today', new \DateTimeZone($expectedTimezone));

        self::assertDate($expected->format('Y-m-d'), Date::today(...$parameters));
    }

    #[DataProvider('dataTimeZones')]
    public function testYesterday(string $expectedTimezone, ...$parameters): void
    {
        $expected = new \DateTimeImmutable('yesterday', new \DateTimeZone($expectedTimezone));

        self::assertDate($expected->format('Y-m-d'), Date::yesterday(...$parameters));
    }

    #[DataProvider('dataTimeZones')]
    public function testTomorrow(string $expectedTimezone, ...$parameters): void
    {
        $expected = new \DateTimeImmutable('tomorrow', new \DateTimeZone($expectedTimezone));

        self::assertDate($expected->format('Y-m-d'), Date::tomorrow(...$parameters));
    }

    public static function dataTimeZones(): iterable
    {
        return [
            ['Europe/Berlin'],
            ['Europe/Berlin', null],
            ['UTC', TimeZone::utc()],
            ['America/Los_Angeles', TimeZone::fromString('America/Los_Angeles')],
        ];
    }

    #[DataProvider('dataFromString')]
    public function testFromString(string $expected, string $date): void
    {
        self::assertDate($expected, Date::fromString($date));
    }

    public static function dataFromString(): iterable
    {
        return [
            [date('Y-m-d'), 'now'],
            [date('Y-m-d'), 'today'],
            ['2018-06-03', '2018-06-03'],
            ['2018-09-08', '2018-09-08 22:07:02'],
            ['2018-09-08', '@'.strtotime('2018-09-08 22:07:02')],
            ['2018-05-15', '2018-05-14 17:00 +10hours'],
        ];
    }

    #[DataProvider('dataFromStringInvalid')]
    public function testFromStringInvalid(string $expected, string $date): void
    {
        $this->expectException(InvalidStringException::class);
        $this->expectExceptionMessage($expected);

        Date::fromString($date);
    }

    public static function dataFromStringInvalid(): iterable
    {
        return [
            ['The date string can not be empty (use "today" for current date).', ''],
            ['Failed to parse time string (foo) at position 0 (f): The timezone could not be found in the database.', 'foo'],
            ['Failed to parse time string (2019-01-32) at position 9 (2): Unexpected character.', '2019-01-32'],
        ];
    }

    public function testFromFormat(): void
    {
        self::assertDate('2018-10-03', Date::fromFormat('d.m.Y', '03.10.2018'));
    }

    public function testFromFormatInvalid(): void
    {
        $this->expectException(FormatMismatchException::class);

        Date::fromFormat('d.m.Y', '2018-10-03');
    }

    public function testFromParts(): void
    {
        self::assertDate('2018-06-17', Date::fromParts(2018, 6, 17));
    }

    #[DataProvider('dataFromPartsInvalid')]
    public function testFromPartsInvalid(string $expected, int $year, int $month, int $day): void
    {
        $this->expectException(InvalidPartException::class);
        $this->expectExceptionMessage($expected);

        Date::fromParts($year, $month, $day);
    }

    public static function dataFromPartsInvalid(): iterable
    {
        return [
            ['Month part must be between 1 and 12, but -1 given.', 2019, -1, 1],
            ['Month part must be between 1 and 12, but 0 given.', 2019, 0, 1],
            ['Month part must be between 1 and 12, but 13 given.', 2019, 13, 1],
            ['Day part must be between 1 and 31, but -1 given.', 2019, 1, -1],
            ['Day part must be between 1 and 31, but 0 given.', 2019, 4, 0],
            ['Day part for month 2 of year 2019 must be between 1 and 28, but 29 given.', 2019, 2, 29],
        ];
    }

    public function testFromTimestamp(): void
    {
        $timestamp = strtotime('2018-06-17 01:05:00');
        self::assertDate('2018-06-17', Date::fromTimestamp($timestamp));
    }

    #[DataProvider('dataFromNative')]
    public function testFromNative(string $expected, \DateTimeInterface $date): void
    {
        self::assertDate($expected, Date::fromNative($date));
    }

    public static function dataFromNative(): iterable
    {
        return [
            ['2018-09-13', new \DateTime('2018-09-13 23:00:00')],
            ['2018-09-14', new \DateTimeImmutable('2018-09-14 00:15:00')],
            ['2018-09-15', new \DateTimeImmutable('2018-09-15 23:45:00', new \DateTimeZone('UTC'))],
            ['2018-09-15', new \DateTimeImmutable('2018-09-15 23:45:00', new \DateTimeZone('America/Los_Angeles'))],
        ];
    }

    #[DataProvider('dataCast')]
    public function testCast(string $expected, $date): void
    {
        self::assertDate($expected, Date::cast($date));
    }

    public static function dataCast(): iterable
    {
        return [
            ['2018-06-17', strtotime('2018-06-17 23:35:00')],
            ['2018-06-03', '2018-06-03'],
            ['2018-09-14', new \DateTimeImmutable('2018-09-14 00:15:00')],
            ['2018-09-15', Date::fromString('2018-09-15')],
            ['2018-09-16', DateTime::fromString('2018-09-16 00:25:30')],
        ];
    }

    public function testToIso(): void
    {
        $expected = '2020-07-26';
        $date = Date::fromString($expected);

        self::assertSame($expected, $date->toIso());
        self::assertSame($expected, (string) $date);
    }

    public function testFormatIntl(): void
    {
        $date = Date::fromString('2018-12-01');

        self::assertSame('1. Dezember 2018', $date->formatIntl());
        self::assertSame('01.12.2018', $date->formatIntl(\IntlDateFormatter::SHORT));
        self::assertSame('Samstag, 1.12.2018 0:00:00.0000 UTC', $date->formatIntl(null, 'EEEE, d.MM.yyyy H:mm:ss.SSSS VV'));
    }

    #[DataProvider('dataIsPast')]
    public function testIsPast(bool $expected, Date $date): void
    {
        self::assertSame($expected, $date->isPast());
    }

    public static function dataIsPast(): iterable
    {
        return [
            [false, Date::today()],
            [false, Date::fromParts(idate('Y') + 1, 1, 1)],
            [true, Date::yesterday()],
            [true, Date::fromString('2019-12-31')],
        ];
    }

    #[DataProvider('dataIsFuture')]
    public function testIsFuture(bool $expected, Date $date): void
    {
        self::assertSame($expected, $date->isFuture());
    }

    public static function dataIsFuture(): iterable
    {
        return [
            [false, Date::today()],
            [false, Date::fromString('2019-12-31')],
            [true, Date::tomorrow()],
            [true, Date::fromParts(idate('Y') + 1, 1, 1)],
        ];
    }

    #[DataProvider('dataIsToday')]
    public function testIsToday(bool $expected, Date $date): void
    {
        self::assertSame($expected, $date->isToday());
    }

    public static function dataIsToday(): iterable
    {
        return [
            [false, Date::yesterday()],
            [false, Date::tomorrow()],
            [false, Date::fromString('2019-12-31')],
            [false, Date::today()->addYears(1)],
            [true, Date::today()],
        ];
    }

    #[DataProvider('dataTimeZones')]
    public function testToDateTime(string $expectedTimezone, ...$parameters): void
    {
        $dateTime = Date::fromString('2018-12-01')->toDateTime(...$parameters);

        self::assertInstanceOf(DateTime::class, $dateTime);
        self::assertSame('2018-12-01 00:00:00.000000', $dateTime->format('Y-m-d H:i:s.u'));
        self::assertSame($expectedTimezone, $dateTime->getTimeZone()->getName());
    }

    public function testToNative(): void
    {
        $dateTime = Date::fromString('2018-12-01')->toNative();

        self::assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        self::assertSame('2018-12-01 00:00:00.000000 Europe/Berlin', $dateTime->format('Y-m-d H:i:s.u e'));
    }

    public function testToMutable(): void
    {
        $dateTime = Date::fromString('2018-12-01')->toMutable();

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame('2018-12-01 00:00:00.000000 Europe/Berlin', $dateTime->format('Y-m-d H:i:s.u e'));
    }

    public function testToTimestamp(): void
    {
        $timestamp = 1594850400;
        $date = Date::fromTimestamp($timestamp);

        self::assertSame($timestamp, $date->toTimestamp());
    }

    public function testSetState(): void
    {
        $dateString = '2018-12-08';
        $date = null;
        eval('$date = '.var_export(Date::fromString($dateString), true).';');

        self::assertDate($dateString, $date);
    }

    public function testSerialization(): void
    {
        $dateString = '2020-07-16';

        self::assertDate($dateString, unserialize(serialize(Date::fromString($dateString))));
    }

    public function testDebugInfo(): void
    {
        $date = Date::fromString('2020-07-16');

        $expected = [
            'date' => '2020-07-16',
        ];

        self::assertSame($expected, $date->__debugInfo());
    }

    // === DateTrait ===

    public function testGetter(): void
    {
        $date = Date::fromString('2020-11-04');

        self::assertSame(2020, $date->getYear());
        self::assertSame(11, $date->getMonth());
        self::assertSame(4, $date->getDay());
        self::assertSame(3, $date->getWeekday());
    }

    #[DataProvider('dataGetQuarter')]
    public function testGetQuarter(int $expected, string $date): void
    {
        $date = Date::fromString($date);

        self::assertSame($expected, $date->getQuarter());
    }

    public static function dataGetQuarter(): iterable
    {
        return [
            [1, '2020-01-01'],
            [1, '2020-03-31'],
            [2, '2020-04-01'],
            [2, '2020-06-30'],
            [3, '2020-07-01'],
            [3, '2020-09-30'],
            [4, '2020-10-01'],
            [4, '2020-12-31'],
        ];
    }

    #[DataProvider('dataIsFirstDayOfYear')]
    public function testIsFirstDayOfYear(bool $expected, string $date): void
    {
        self::assertSame($expected, Date::fromString($date)->isFirstDayOfYear());
    }

    public static function dataIsFirstDayOfYear(): iterable
    {
        return [
            [true, '2020-01-01'],
            [true, '2021-01-01'],
            [false, '2020-12-31'],
            [false, '2020-02-01'],
            [false, '2020-01-02'],
        ];
    }

    #[DataProvider('dataIsLastDayOfYear')]
    public function testIsLastDayOfYear(bool $expected, string $date): void
    {
        self::assertSame($expected, Date::fromString($date)->isLastDayOfYear());
    }

    public static function dataIsLastDayOfYear(): iterable
    {
        return [
            [true, '2020-12-31'],
            [true, '2021-12-31'],
            [false, '2020-01-01'],
            [false, '2021-12-30'],
            [false, '2020-10-31'],
        ];
    }

    #[DataProvider('dataIsFirstDayOfMonth')]
    public function testIsFirstDayOfMonth(bool $expected, string $date): void
    {
        self::assertSame($expected, Date::fromString($date)->isFirstDayOfMonth());
    }

    public static function dataIsFirstDayOfMonth(): iterable
    {
        return [
            [true, '2020-01-01'],
            [true, '2021-04-01'],
            [false, '2020-10-31'],
            [false, '2020-01-02'],
        ];
    }

    #[DataProvider('dataIsLastDayOfMonth')]
    public function testIsLastDayOfMonth(bool $expected, string $date): void
    {
        self::assertSame($expected, Date::fromString($date)->isLastDayOfMonth());
    }

    public static function dataIsLastDayOfMonth(): iterable
    {
        return [
            [true, '2020-01-31'],
            [true, '2020-02-29'],
            [false, '2020-10-30'],
            [false, '2020-02-28'],
        ];
    }

    #[DataProvider('dataAddYears')]
    public function testAddYears(string $expected, Date $date, int $years): void
    {
        self::assertDate($expected, $date->addYears($years));
    }

    public static function dataAddYears(): iterable
    {
        return [
            ['2022-11-28', Date::fromString('2020-11-28'), 2],
            ['2019-11-28', Date::fromString('2020-11-28'), -1],
            ['2023-11-29', Date::fromString('2020-11-29'), 3],
        ];
    }

    #[DataProvider('dataSubYears')]
    public function testSubYears(string $expected, Date $date, int $years): void
    {
        self::assertDate($expected, $date->subYears($years));
    }

    public static function dataSubYears(): iterable
    {
        return [
            ['2018-11-28', Date::fromString('2020-11-28'), 2],
            ['2021-11-28', Date::fromString('2020-11-28'), -1],
            ['2017-11-29', Date::fromString('2020-11-29'), 3],
        ];
    }

    #[DataProvider('dataAddMonths')]
    public function testAddMonths(string $expected, Date $date, int $months): void
    {
        self::assertDate($expected, $date->addMonths($months));
    }

    public static function dataAddMonths(): iterable
    {
        return [
            ['2021-01-28', Date::fromString('2020-11-28'), 2],
            ['2020-10-28', Date::fromString('2020-11-28'), -1],
            ['2022-03-01', Date::fromString('2020-11-29'), 15],
        ];
    }

    #[DataProvider('dataSubMonths')]
    public function testSubMonths(string $expected, Date $date, int $months): void
    {
        self::assertDate($expected, $date->subMonths($months));
    }

    public static function dataSubMonths(): iterable
    {
        return [
            ['2019-11-28', Date::fromString('2020-01-28'), 2],
            ['2020-12-28', Date::fromString('2020-11-28'), -1],
            ['2019-08-29', Date::fromString('2020-11-29'), 15],
        ];
    }

    #[DataProvider('dataAddWeeks')]
    public function testAddWeeks(string $expected, Date $date, int $weeks): void
    {
        self::assertDate($expected, $date->addWeeks($weeks));
    }

    public static function dataAddWeeks(): iterable
    {
        return [
            ['2020-12-12', Date::fromString('2020-11-28'), 2],
            ['2020-11-21', Date::fromString('2020-11-28'), -1],
            ['2021-01-03', Date::fromString('2020-11-29'), 5],
        ];
    }

    #[DataProvider('dataSubWeeks')]
    public function testSubWeeks(string $expected, Date $date, int $weeks): void
    {
        self::assertDate($expected, $date->subWeeks($weeks));
    }

    public static function dataSubWeeks(): iterable
    {
        return [
            ['2020-11-14', Date::fromString('2020-11-28'), 2],
            ['2020-12-05', Date::fromString('2020-11-28'), -1],
            ['2020-10-25', Date::fromString('2020-11-29'), 5],
        ];
    }

    #[DataProvider('dataAddDays')]
    public function testAddDays(string $expected, Date $date, int $days): void
    {
        self::assertDate($expected, $date->addDays($days));
    }

    public static function dataAddDays(): iterable
    {
        return [
            ['2020-12-01', Date::fromString('2020-11-29'), 2],
            ['2020-11-27', Date::fromString('2020-11-28'), -1],
            ['2021-01-03', Date::fromString('2020-11-29'), 35],
        ];
    }

    #[DataProvider('dataSubDays')]
    public function testSubDays(string $expected, Date $date, int $days): void
    {
        self::assertDate($expected, $date->subDays($days));
    }

    public static function dataSubDays(): iterable
    {
        return [
            ['2020-11-27', Date::fromString('2020-11-29'), 2],
            ['2020-12-01', Date::fromString('2020-11-30'), -1],
            ['2020-11-29', Date::fromString('2021-01-03'), 35],
        ];
    }

    #[DataProvider('dataToFirstDayOfYear')]
    public function testToFirstDayOfYear(string $expected, Date $date): void
    {
        self::assertDate($expected, $date->toFirstDayOfYear());
    }

    public static function dataToFirstDayOfYear(): iterable
    {
        return [
            ['2019-01-01', Date::fromString('2019-01-01')],
            ['2020-01-01', Date::fromString('2020-12-31 23:59:59.999999')],
            ['2020-01-01', Date::fromString('2020-05-29')],
        ];
    }

    #[DataProvider('dataToLastDayOfYear')]
    public function testToLastDayOfYear(string $expected, Date $date): void
    {
        self::assertDate($expected, $date->toLastDayOfYear());
    }

    public static function dataToLastDayOfYear(): iterable
    {
        return [
            ['2019-12-31', Date::fromString('2019-01-01')],
            ['2020-12-31', Date::fromString('2020-12-31')],
            ['2020-12-31', Date::fromString('2020-05-29')],
        ];
    }

    #[DataProvider('dataToFirstDayOfQuarter')]
    public function testToFirstDayOfQuarter(string $expected, Date $date): void
    {
        self::assertDate($expected, $date->toFirstDayOfQuarter());
    }

    public static function dataToFirstDayOfQuarter(): iterable
    {
        return [
            ['2019-01-01', Date::fromString('2019-01-01')],
            ['2020-01-01', Date::fromString('2020-03-31')],
            ['2020-07-01', Date::fromString('2020-08-15')],
            ['2020-04-01', Date::fromString('2020-05-29')],
        ];
    }

    #[DataProvider('dataToLastDayOfQuarter')]
    public function testToLastDayOfQuarter(string $expected, Date $date): void
    {
        self::assertDate($expected, $date->toLastDayOfQuarter());
    }

    public static function dataToLastDayOfQuarter(): iterable
    {
        return [
            ['2019-03-31', Date::fromString('2019-01-01')],
            ['2020-03-31', Date::fromString('2020-03-31')],
            ['2020-09-30', Date::fromString('2020-08-15')],
            ['2020-06-30', Date::fromString('2020-05-29')],
        ];
    }

    #[DataProvider('dataToFirstDayOfMonth')]
    public function testToFirstDayOfMonth(string $expected, Date $date): void
    {
        self::assertDate($expected, $date->toFirstDayOfMonth());
    }

    public static function dataToFirstDayOfMonth(): iterable
    {
        return [
            ['2019-01-01', Date::fromString('2019-01-01')],
            ['2020-03-01', Date::fromString('2020-03-31')],
            ['2020-05-01', Date::fromString('2020-05-29')],
        ];
    }

    #[DataProvider('dataToLastDayOfMonth')]
    public function testToLastDayOfMonth(string $expected, Date $date): void
    {
        self::assertDate($expected, $date->toLastDayOfMonth());
    }

    public static function dataToLastDayOfMonth(): iterable
    {
        return [
            ['2019-01-31', Date::fromString('2019-01-01')],
            ['2020-03-31', Date::fromString('2020-03-31')],
            ['2020-02-29', Date::fromString('2020-02-15')],
        ];
    }

    #[DataProvider('dataToMonth')]
    public function testToMonth(Month $expected, string $date): void
    {
        $date = Date::fromString($date);

        self::assertSame($expected, $date->toMonth());
    }

    public static function dataToMonth(): iterable
    {
        return [
            [Month::March, '2019-03-12'],
            [Month::February, '2020-02-29'],
            [Month::July, '2020-07-01'],
        ];
    }

    #[DataProvider('dataToWeekday')]
    public function testToWeekday(Weekday $expected, string $date): void
    {
        $date = Date::fromString($date);

        self::assertSame($expected, $date->toWeekday());
    }

    public static function dataToWeekday(): iterable
    {
        return [
            [Weekday::Monday, '2019-07-15'],
            [Weekday::Thursday, '2020-12-31'],
            [Weekday::Sunday, '2021-01-03'],
        ];
    }

    #[DataProvider('dataToPrevWeekday')]
    public function testToPrevWeekday(string $expected, string $date, $weekday): void
    {
        $date = Date::fromString($date);

        self::assertDate($expected, $date->toPrevWeekday($weekday));
    }

    public static function dataToPrevWeekday(): iterable
    {
        return [
            ['2019-07-15', '2019-07-15', Weekday::Monday],
            ['2019-07-14', '2019-07-15', Weekday::Sunday],
            ['2020-12-26', '2021-01-01', Weekday::Saturday],
        ];
    }

    #[DataProvider('dataToNextWeekday')]
    public function testToNextWeekday(string $expected, string $date, $weekday): void
    {
        $date = Date::fromString($date);

        self::assertDate($expected, $date->toNextWeekday($weekday));
    }

    public static function dataToNextWeekday(): iterable
    {
        return [
            ['2019-07-14', '2019-07-14', Weekday::Sunday],
            ['2019-07-15', '2019-07-14', Weekday::Monday],
            ['2021-01-01', '2020-12-26', Weekday::Friday],
        ];
    }

    // === CompareTrait ===

    #[DataProvider('dataMin')]
    public function testMin(int $expectedIndex, Date ...$dates): void
    {
        self::assertSame($dates[$expectedIndex], Date::min(...$dates));
    }

    public static function dataMin(): iterable
    {
        return [
            [0, Date::fromString('2020-07-19')],
            [0, Date::fromString('2020-07-19'), Date::fromString('2020-07-20')],
            [1, Date::today(), Date::fromString('2020-07-17'), Date::fromString('2020-07-18')],
        ];
    }

    #[DataProvider('dataMax')]
    public function testMax(int $expectedIndex, Date ...$dates): void
    {
        self::assertSame($dates[$expectedIndex], Date::max(...$dates));
    }

    public static function dataMax(): iterable
    {
        return [
            [0, Date::fromString('2020-07-19')],
            [0, Date::fromString('2020-07-20'), Date::fromString('2020-07-19')],
            [1, Date::fromString('2018-12-31'), Date::fromString('2020-07-19'), Date::fromString('2020-04-24')],
        ];
    }

    #[DataProvider('dataDiff')]
    public function testDiff(string $expected, Date $dateTime, Date $other, ...$parameters): void
    {
        $duration = $dateTime->diff($other, ...$parameters);

        self::assertInstanceOf(Duration::class, $duration);
        self::assertSame($expected, $duration->toIso());
    }

    public static function dataDiff(): iterable
    {
        return [
            ['PT0S', Date::fromString('2020-07-19'), Date::fromString('2020-07-19')],
            ['P1Y4D', Date::fromString('2020-07-19'), Date::fromString('2021-07-23')],
            ['-P2D', Date::fromString('2020-07-19'), Date::fromString('2020-07-17')],
            ['P2D', Date::fromString('2020-07-19'), Date::fromString('2020-07-17'), true],
            ['-P1Y5M', Date::fromString('2020-07-19'), Date::fromString('2019-02-19'), false],
        ];
    }

    #[DataProvider('dataCompareTo')]
    public function testCompareTo(int $expected, Date $dateTime, Date $other): void
    {
        self::assertSame($expected, $dateTime->compareTo($other));
        self::assertSame(0 === $expected, $dateTime->equals($other));
        self::assertSame(1 === $expected, $dateTime->greaterThan($other));
        self::assertSame(-1 !== $expected, $dateTime->greaterEquals($other));
        self::assertSame(-1 === $expected, $dateTime->lowerThan($other));
        self::assertSame(1 !== $expected, $dateTime->lowerEquals($other));
    }

    public static function dataCompareTo(): iterable
    {
        return [
            [0, Date::fromString('2020-07-19'), Date::fromString('2020-07-19')],
            [-1, Date::fromString('2020-07-19'), Date::fromString('2021-03-01')],
            [1, Date::fromString('2020-07-19'), Date::fromString('2020-07-18')],
        ];
    }

    // === FormatTrait ===

    public function testJsonSerialize(): void
    {
        $date = Date::fromString('2020-07-26');

        self::assertJsonStringEqualsJsonString('"2020-07-26"', json_encode($date));
    }

    // === ModifyTrait ===

    #[DataProvider('dataModify')]
    public function testModify(string $expected, string $modify): void
    {
        $date = Date::fromString('2020-07-26');

        self::assertDate($expected, $date->modify($modify));
    }

    public static function dataModify(): iterable
    {
        return [
            ['2021-06-26', '+1year -1month'],
            ['2020-07-26', '+23 hours'],
            ['2020-07-27', '+30 hours'],
        ];
    }

    public function testModifyInvalid(): void
    {
        $this->expectException(InvalidStringException::class);
        $this->expectExceptionMessageMatches('/^Failed to parse time string \(foo\)/');

        Date::fromString('2024-02-09')->modify('foo');
    }

    #[DataProvider('dataAdd')]
    public function testAdd(string $expected, string $duration): void
    {
        $date = Date::fromString('2020-07-26');

        self::assertDate($expected, $date->add(Duration::fromString($duration)));
    }

    public static function dataAdd(): iterable
    {
        return [
            ['2020-08-01', 'P1WT-1H'],
            ['2019-07-25', '-P1YT2M'],
        ];
    }

    #[DataProvider('dataSub')]
    public function testSub(string $expected, string $duration): void
    {
        $date = Date::fromString('2020-07-26');

        self::assertDate($expected, $date->sub(Duration::fromString($duration)));
    }

    public static function dataSub(): iterable
    {
        return [
            ['2020-07-19', 'P1WT-2H'],
            ['2021-07-26', '-P1YT2M'],
        ];
    }

    // === Assertions ===

    /**
     * @param Date $date
     */
    private static function assertDate(string $expected, $date): void
    {
        self::assertInstanceOf(Date::class, $date);
        self::assertSame('00:00:00.000000 UTC', $date->format('H:i:s.u e'));
        self::assertSame($expected, $date->format('Y-m-d'));
    }
}
