<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Tests;

use HillValley\Fluxcap\Date;
use HillValley\Fluxcap\DateTime;
use HillValley\Fluxcap\Duration;
use HillValley\Fluxcap\Exception\FormatMismatchException;
use HillValley\Fluxcap\Exception\InvalidPartException;
use HillValley\Fluxcap\Exception\InvalidStringException;
use HillValley\Fluxcap\Month;
use HillValley\Fluxcap\Time;
use HillValley\Fluxcap\TimeZone;
use HillValley\Fluxcap\Weekday;
use PHPUnit\Framework\TestCase;
use const PHP_VERSION_ID;

/**
 * @internal
 * @covers \HillValley\Fluxcap\Base\IntlFormatter
 * @covers \HillValley\Fluxcap\DateTime
 */
final class DateTimeTest extends TestCase
{
    public function testConstruct(): void
    {
        $method = new \ReflectionMethod(DateTime::class, '__construct');

        self::assertTrue($method->isPrivate());
    }

    /** @dataProvider dataTimeZones */
    public function testNow(string $expectedTimezone, ...$parameters): void
    {
        self::assertDateTimeNow(DateTime::now(...$parameters), $expectedTimezone);
    }

    public function testUtcNow(): void
    {
        self::assertDateTimeNow(DateTime::utcNow(), 'UTC');
    }

    /** @dataProvider dataTimeZones */
    public function testToday(string $expectedTimezone, ...$parameters): void
    {
        $expected = new \DateTimeImmutable('today', new \DateTimeZone($expectedTimezone));
        $expected = $expected->format('Y-m-d').' 00:00:00.000000 '.$expectedTimezone;

        self::assertDateTime($expected, DateTime::today(...$parameters));
    }

    /** @dataProvider dataTimeZones */
    public function testYesterday(string $expectedTimezone, ...$parameters): void
    {
        $expected = new \DateTimeImmutable('yesterday', new \DateTimeZone($expectedTimezone));
        $expected = $expected->format('Y-m-d').' 00:00:00.000000 '.$expectedTimezone;

        self::assertDateTime($expected, DateTime::yesterday(...$parameters));
    }

    /** @dataProvider dataTimeZones */
    public function testTomorrow(string $expectedTimezone, ...$parameters): void
    {
        $expected = new \DateTimeImmutable('tomorrow', new \DateTimeZone($expectedTimezone));
        $expected = $expected->format('Y-m-d').' 00:00:00.000000 '.$expectedTimezone;

        self::assertDateTime($expected, DateTime::tomorrow(...$parameters));
    }

    public function dataTimeZones(): iterable
    {
        return [
            ['Europe/Berlin'],
            ['Europe/Berlin', null],
            ['UTC', TimeZone::utc()],
            ['America/Los_Angeles', TimeZone::fromString('America/Los_Angeles')],
        ];
    }

    /** @dataProvider dataTimeZones */
    public function testFromStringNow(string $expectedTimezone, ...$parameters): void
    {
        self::assertDateTimeNow(DateTime::fromString('now', ...$parameters), $expectedTimezone);
    }

    /** @dataProvider dataFromString */
    public function testFromString(string $expected, string $dateTime, ?TimeZone $timeZone = null): void
    {
        self::assertDateTime($expected, DateTime::fromString($dateTime, $timeZone));
    }

    public function dataFromString(): iterable
    {
        return [
            ['2018-12-08 00:00:00.000000 Europe/Berlin', '2018-12-08'],
            [date('Y-m-d').' 15:32:00.000000 Europe/Berlin', '15:32'],
            ['2018-05-20 13:40:15.456123 Europe/Berlin', '2018-05-20 13:40:15.456123'],
            ['2018-11-20 13:40:15.456123 -07:00', '2018-11-20 13:40:15.456123-07:00'],
            ['2018-11-20 13:40:15.456123 CDT', '2018-11-20 13:40:15.456123 CDT'],
            ['2018-11-20 13:40:15.456123 EET', '2018-11-20 13:40:15.456123', TimeZone::fromString('EET')],
            ['2018-11-20 12:40:15.456123 Europe/Berlin', '2018-11-20 13:40:15.456123 EET', TimeZone::default()],
            ['2018-09-08 20:07:02.000000 +00:00', '@'.strtotime('2018-09-08 22:07:02')],
            ['2018-12-06 03:30:00.000000 Europe/Berlin', '2018-12-05 17:30 +10hours'],
            ['2018-12-04 23:55:00.123456 Europe/Berlin', '2018-12-05 00:00:00.123456 -5minutes'],
        ];
    }

    /** @dataProvider dataFromStringInvalid */
    public function testFromStringInvalid(string $expected, string $dateTime): void
    {
        $this->expectException(InvalidStringException::class);
        $this->expectExceptionMessage($expected);

        DateTime::fromString($dateTime);
    }

    public function dataFromStringInvalid(): iterable
    {
        return [
            ['The date-time string can not be empty (use "now" for current time).', ''],
            ['Failed to parse time string (foo) at position 0 (f): The timezone could not be found in the database.', 'foo'],
            ['Failed to parse time string (2019-01-32) at position 9 (2): Unexpected character.', '2019-01-32'],
        ];
    }

    public function testUtcFromStringNow(): void
    {
        self::assertDateTimeNow(DateTime::utcFromString('now'), 'UTC');
    }

    /** @dataProvider dataUtcFromString */
    public function testUtcFromString(string $expected, string $dateTime): void
    {
        self::assertDateTime($expected, DateTime::utcFromString($dateTime));
    }

    public function dataUtcFromString(): iterable
    {
        return [
            ['2018-12-08 00:00:00.000000 UTC', '2018-12-08'],
            [gmdate('Y-m-d').' 15:32:00.000000 UTC', '15:32'],
            ['2018-05-20 13:40:15.456123 UTC', '2018-05-20 13:40:15.456123'],
            ['2018-11-20 20:40:15.456123 UTC', '2018-11-20 13:40:15.456123-07:00'],
            ['2018-11-20 12:40:15.456123 UTC', '2018-11-20 13:40:15.456123 Europe/Berlin'],
            ['2018-09-08 20:07:02.000000 UTC', '@'.strtotime('2018-09-08 22:07:02')],
            ['2018-12-06 03:30:00.000000 UTC', '2018-12-05 17:30 +10hours'],
            ['2018-12-04 23:55:00.123456 UTC', '2018-12-05 00:00:00.123456 -5minutes'],
        ];
    }

    /** @dataProvider dataTimeZones */
    public function testFromUtcStringNow(string $expectedTimezone, ...$parameters): void
    {
        self::assertDateTimeNow(DateTime::fromUtcString('now', ...$parameters), $expectedTimezone);
    }

    /** @dataProvider dataFromUtcString */
    public function testFromUtcString(string $expected, string $dateTime, ?TimeZone $timeZone = null): void
    {
        self::assertDateTime($expected, DateTime::fromUtcString($dateTime, $timeZone));
    }

    public function dataFromUtcString(): iterable
    {
        return [
            ['2018-12-08 01:00:00.000000 Europe/Berlin', '2018-12-08'],
            [gmdate('Y-m-d ').date('H:i:s', strtotime('15:32+00:00')).'.000000 Europe/Berlin', '15:32'],
            ['2018-05-20 15:40:15.456123 Europe/Berlin', '2018-05-20 13:40:15.456123'],
            ['2018-11-20 21:40:15.456123 Europe/Berlin', '2018-11-20 13:40:15.456123-07:00'],
            ['2018-11-20 19:40:15.456123 Europe/Berlin', '2018-11-20 13:40:15.456123 CDT'],
            ['2018-11-20 15:40:15.456123 EET', '2018-11-20 13:40:15.456123', TimeZone::fromString('EET')],
            ['2018-11-20 12:40:15.456123 Europe/Berlin', '2018-11-20 13:40:15.456123 EET', TimeZone::default()],
            ['2018-09-08 22:07:02.000000 Europe/Berlin', '@'.strtotime('2018-09-08 22:07:02')],
            ['2018-12-06 04:30:00.000000 Europe/Berlin', '2018-12-05 17:30 +10hours'],
            ['2018-12-05 00:55:00.123456 Europe/Berlin', '2018-12-05 00:00:00.123456 -5minutes'],
        ];
    }

    /** @dataProvider dataFromFormat */
    public function testFromFormat(string $expected, string $format, string $dateTime, ?TimeZone $timeZone = null): void
    {
        self::assertDateTime($expected, DateTime::fromFormat($format, $dateTime, $timeZone));
    }

    public function dataFromFormat(): iterable
    {
        return [
            ['2018-12-08 15:32:00.000000 Europe/Berlin', 'j.n.Y, i.H', '8.12.2018, 32.15'],
            ['2018-12-08 09:13:45.123000 Europe/Berlin', 'u s i G d m Y', '123 45 13 9 08 12 2018'],
            ['2018-12-08 09:13:45.123000 EET', 'j.m.Y, G:i:s.u', '8.12.2018, 9:13:45.123', TimeZone::fromString('EET')],
            ['2018-12-08 09:13:45.123000 EET', 'j.m.Y, G:i:s.u e', '8.12.2018, 9:13:45.123 EET'],
            ['2018-12-08 08:13:45.123000 Europe/Berlin', 'j.m.Y, G:i:s.u e', '8.12.2018, 9:13:45.123 EET', TimeZone::default()],
        ];
    }

    public function testFromFormatInvalid(): void
    {
        $this->expectException(FormatMismatchException::class);

        DateTime::fromFormat('d.m.Y, H:i:s', '2018-10-03 20:10:00');
    }

    /** @dataProvider dataFromParts */
    public function testFromParts(string $expected, ...$parts): void
    {
        self::assertDateTime($expected, DateTime::fromParts(...$parts));
    }

    public function dataFromParts(): iterable
    {
        return [
            ['2018-05-04 00:00:00.000000 Europe/Berlin', 2018, 5, 4],
            ['2018-12-04 00:00:00.000000 Europe/Berlin', 2018, 12, 4],
            ['2018-12-04 05:00:00.000000 Europe/Berlin', 2018, 12, 4, 5],
            ['2018-12-04 11:43:00.000000 Europe/Berlin', 2018, 12, 4, 11, 43],
            ['2018-12-04 06:12:50.000000 Europe/Berlin', 2018, 12, 4, 6, 12, 50],
            ['2018-12-04 00:05:13.000192 Europe/Berlin', 2018, 12, 4, 0, 5, 13, 192],
            ['2018-12-04 00:05:13.000192 America/Los_Angeles', 2018, 12, 4, 0, 5, 13, 192, TimeZone::fromString('America/Los_Angeles')],
        ];
    }

    /** @dataProvider dataFromPartsInvalid */
    public function testFromPartsInvalid(string $expected, ...$parts): void
    {
        $this->expectException(InvalidPartException::class);
        $this->expectExceptionMessage($expected);

        DateTime::fromParts(...$parts);
    }

    public function dataFromPartsInvalid(): iterable
    {
        return [
            ['Month part must be between 1 and 12, but -1 given.', 2019, -1, 1],
            ['Month part must be between 1 and 12, but 0 given.', 2019, 0, 1],
            ['Month part must be between 1 and 12, but 13 given.', 2019, 13, 1],
            ['Day part must be between 1 and 31, but -1 given.', 2019, 1, -1],
            ['Day part must be between 1 and 31, but 0 given.', 2019, 4, 0],
            ['Day part for month 2 of year 2019 must be between 1 and 28, but 29 given.', 2019, 2, 29],
            ['Hour part must be between 0 and 23, but -1 given.', 2019, 3, 17, -1],
            ['Hour part must be between 0 and 23, but 24 given.', 2019, 3, 17, 24],
            ['Minute part must be between 0 and 59, but -1 given.', 2019, 3, 17, 13, -1],
            ['Minute part must be between 0 and 59, but 60 given.', 2019, 3, 17, 0, 60],
            ['Seconds part must be between 0 and 59, but -1 given.', 2019, 3, 17, 7, 45, -1],
            ['Seconds part must be between 0 and 59, but 60 given.', 2019, 3, 17, 7, 0, 60],
            ['Microseconds part must be between 0 and 999999, but -1 given.', 2019, 3, 17, 7, 25, 40, -1],
            ['Microseconds part must be between 0 and 999999, but 1000000 given.', 2019, 3, 17, 7, 25, 0, 1000000],
        ];
    }

    /** @dataProvider dataFromTimestamp */
    public function testFromTimestamp(string $expected, int $timestamp, ?TimeZone $timeZone = null): void
    {
        self::assertDateTime($expected, DateTime::fromTimestamp($timestamp, $timeZone));
    }

    public function dataFromTimestamp(): iterable
    {
        return [
            ['2018-12-01 01:05:20.000000 Europe/Berlin', strtotime('2018-12-01 01:05:20')],
            ['2018-12-01 02:05:20.000000 EET', strtotime('2018-12-01 01:05:20'), TimeZone::fromString('EET')],
        ];
    }

    /** @dataProvider dataFromNative */
    public function testFromNative(string $expected, \DateTimeInterface $dateTime): void
    {
        self::assertDateTime($expected, DateTime::fromNative($dateTime));
    }

    public function dataFromNative(): iterable
    {
        return [
            ['2018-09-13 23:00:00.000000 Europe/Berlin', new \DateTime('2018-09-13 23:00:00')],
            ['2018-09-14 00:15:00.123000 Europe/Berlin', new \DateTimeImmutable('2018-09-14 00:15:00.123000')],
            ['2018-09-15 23:45:00.000000 UTC', new \DateTimeImmutable('2018-09-15 23:45:00', new \DateTimeZone('UTC'))],
            ['2018-09-15 23:45:00.000000 EET', new \DateTimeImmutable('2018-09-15 23:45:00', new \DateTimeZone('EET'))],
        ];
    }

    /** @dataProvider dataCombine */
    public function testCombine(string $expected, Date $date, Time $time, ?TimeZone $timeZone = null): void
    {
        self::assertDateTime($expected, DateTime::combine($date, $time, $timeZone));
    }

    public function dataCombine(): iterable
    {
        return [
            ['2018-10-12 04:30:15.123456 Europe/Berlin', Date::fromString('2018-10-12'), Time::fromString('04:30:15.123456')],
            ['2018-10-12 04:30:15.123456 EET', Date::fromString('2018-10-12'), Time::fromString('04:30:15.123456'), TimeZone::fromString('EET')],
        ];
    }

    /** @dataProvider dataCast */
    public function testCast(string $expected, $dateTime): void
    {
        self::assertDateTime($expected, DateTime::cast($dateTime));
    }

    public function dataCast(): iterable
    {
        return [
            ['2018-06-17 23:35:00.000000 Europe/Berlin', strtotime('2018-06-17 23:35:00')],
            ['2018-06-03 01:00:45.768123 Europe/Berlin', '2018-06-03 01:00:45.768123'],
            ['2018-09-14 00:15:00.000000 Europe/Berlin', new \DateTimeImmutable('2018-09-14 00:15:00')],
            ['2018-09-14 00:15:00.000000 EET', new \DateTimeImmutable('2018-09-14 00:15:00', new \DateTimeZone('EET'))],
            ['2018-12-08 00:25:30.000000 Europe/Berlin', DateTime::fromString('2018-12-08 00:25:30')],
            ['2018-12-08 00:25:30.000000 EET', DateTime::fromString('2018-12-08 00:25:30', TimeZone::fromString('EET'))],
            ['2018-12-08 00:00:00.000000 Europe/Berlin', Date::fromString('2018-12-08')],
            ['1970-01-01 05:30:00.123000 Europe/Berlin', Time::fromString('05:30:00.123')],
        ];
    }

    public function testGetTimeZone(): void
    {
        $timeZone = TimeZone::fromString('America/Los_Angeles');
        $dateTime = DateTime::fromString('2018-12-08 12:45', $timeZone);

        self::assertSame($timeZone, $dateTime->getTimeZone());
    }

    public function testGetOffset(): void
    {
        $timeZone = TimeZone::fromString('America/Los_Angeles');
        $dateTime = DateTime::fromString('2018-07-08 12:45', $timeZone);

        self::assertSame(-25200, $dateTime->getOffset());
    }

    /** @dataProvider dataToIso */
    public function testToIso(string $expected, DateTime $dateTime): void
    {
        self::assertSame($expected, $dateTime->toIso());
        self::assertSame($expected, (string) $dateTime);
    }

    public function dataToIso(): iterable
    {
        return [
            ['2018-12-08T12:45:00.000000Z', DateTime::utcFromString('2018-12-08 12:45')],
            ['2018-12-08T12:45:10.456123Z', DateTime::utcFromString('2018-12-08 12:45:10.456123')],
            ['2018-12-08T12:45:10.000000+01:00', DateTime::fromString('2018-12-08 12:45:10.000000')],
            ['2018-05-20T12:45:10.123456+02:00', DateTime::fromString('2018-05-20 12:45:10.123456')],
            ['2018-05-20T12:45:10.123456-07:00', DateTime::fromString('2018-05-20 12:45:10.123456', TimeZone::fromString('America/Los_Angeles'))],
        ];
    }

    /** @dataProvider dataFormatIntl */
    public function testFormatIntl(string $expected, ...$parameters): void
    {
        $dateTime = DateTime::fromString('2020-07-02 03:20:50.123456');

        self::assertSame($expected, $dateTime->formatIntl(...$parameters));
    }

    public function dataFormatIntl(): iterable
    {
        return [
            ['2. Juli 2020 um 03:20:50'],
            ['02.07.2020, 03:20', \IntlDateFormatter::SHORT],
            ['03:20', \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT],
            ['2. Jul. 2020, 03:20:50 MESZ', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::LONG],
            ['2.07.2020 3:20:50.1230 Europe/Berlin', null, null, 'd.MM.yyyy H:mm:ss.SSSS VV'],
        ];
    }

    public function testFormatIntlDate(): void
    {
        $dateTime = DateTime::fromString('2020-07-02 03:20:50.123456');
        self::assertSame('2. Juli 2020', $dateTime->formatIntlDate());
        self::assertSame('02.07.2020', $dateTime->formatIntlDate(\IntlDateFormatter::SHORT));
    }

    public function testFormatIntlTime(): void
    {
        $dateTime = DateTime::fromString('2020-07-02 03:20:50.123456');
        self::assertSame('03:20:50', $dateTime->formatIntlTime());
        self::assertSame('03:20', $dateTime->formatIntlTime(\IntlDateFormatter::SHORT));
    }

    /** @dataProvider dataIsPast */
    public function testIsPast(bool $expected, DateTime $dateTime): void
    {
        self::assertSame($expected, $dateTime->isPast());
    }

    public function dataIsPast(): iterable
    {
        return [
            [false, DateTime::now()->addSeconds(2)],
            [false, DateTime::now(TimeZone::fromString('America/Los_Angeles'))->addSeconds(2)],
            [false, DateTime::fromParts(idate('Y') + 1, 1, 1)],
            [true, DateTime::now()->subSeconds(1)],
            [true, DateTime::now(TimeZone::fromString('Asia/Tokyo'))->subSeconds(1)],
            [true, DateTime::fromString('2019-12-31')],
        ];
    }

    /** @dataProvider dataIsFuture */
    public function testIsFuture(bool $expected, DateTime $dateTime): void
    {
        self::assertSame($expected, $dateTime->isFuture());
    }

    public function dataIsFuture(): iterable
    {
        return [
            [false, DateTime::now()],
            [false, DateTime::now(TimeZone::fromString('Asia/Tokyo'))],
            [false, DateTime::fromString('2019-12-31')],
            [true, DateTime::now()->addSeconds(3)],
            [true, DateTime::now(TimeZone::fromString('America/Los_Angeles'))->addSeconds(3)],
            [true, DateTime::fromParts(idate('Y') + 1, 1, 1)],
        ];
    }

    /** @dataProvider dataIsToday */
    public function testIsToday(bool $expected, DateTime $dateTime): void
    {
        self::assertSame($expected, $dateTime->isToday());
    }

    public function dataIsToday(): iterable
    {
        return [
            [false, DateTime::fromString('yesterday 23:59:59.999999')],
            [false, DateTime::tomorrow()],
            [false, DateTime::fromString('2019-12-31')],
            [false, DateTime::today()->addYears(1)],
            [true, DateTime::now()],
            [true, DateTime::today()],
            [true, DateTime::fromString('23:59:59.999999')],
            [true, DateTime::today()->toTimeZone(TimeZone::fromString('America/Los_Angeles'))],
            [true, DateTime::fromString('23:59:59.999999')->toTimeZone(TimeZone::fromString('Asia/Tokyo'))],
        ];
    }

    public function testToTimeZone(): void
    {
        $dateTime = DateTime::fromString('2020-07-02 03:20:50.123456');

        self::assertSame($dateTime, $dateTime->toTimeZone(TimeZone::fromString('Europe/Berlin')));

        $dateTime = $dateTime->toTimeZone(TimeZone::fromString('America/Los_Angeles'));
        self::assertDateTime('2020-07-01 18:20:50.123456 America/Los_Angeles', $dateTime);
    }

    public function testToDefaultTimeZone(): void
    {
        $dateTime = DateTime::fromString('2020-07-02 03:20:50.123456', TimeZone::fromString('Europe/Berlin'));
        self::assertSame($dateTime, $dateTime->toDefaultTimeZone());

        $dateTime = DateTime::fromString('2020-07-01 18:20:50.123456', TimeZone::fromString('America/Los_Angeles'));
        self::assertDateTime('2020-07-02 03:20:50.123456 Europe/Berlin', $dateTime->toDefaultTimeZone());
    }

    public function testToUtc(): void
    {
        $dateTime = DateTime::fromString('2020-07-02 03:20:50.123456', TimeZone::fromString('UTC'));
        self::assertSame($dateTime, $dateTime->toUtc());

        $dateTime = DateTime::fromString('2020-07-02 03:20:50.123456', TimeZone::fromString('GMT'));
        self::assertDateTime('2020-07-02 03:20:50.123456 UTC', $dateTime->toUtc());

        $dateTime = DateTime::fromString('2020-07-02 03:20:50.123456');
        self::assertDateTime('2020-07-02 01:20:50.123456 UTC', $dateTime->toUtc());
    }

    /** @dataProvider dataToMidnight */
    public function testToMidnight(string $expected, DateTime $dateTime): void
    {
        self::assertDateTime($expected, $dateTime->toMidnight());
    }

    public function dataToMidnight(): iterable
    {
        return [
            ['2020-11-15 00:00:00.000000 Europe/Berlin', DateTime::fromString('2020-11-15 00:00:00.000000')],
            ['2020-07-02 00:00:00.000000 Europe/Berlin', DateTime::fromString('2020-07-02 23:59:59.999999')],
            ['2020-07-02 00:00:00.000000 UTC', DateTime::fromString('2020-07-02 03:20:50.123456', TimeZone::fromString('UTC'))],
        ];
    }

    public function testToDate(): void
    {
        $date = DateTime::fromString('2018-12-08 12:45:00.123456')->toDate();

        self::assertInstanceOf(Date::class, $date);
        self::assertSame('2018-12-08 00:00:00.000000 UTC', $date->format('Y-m-d H:i:s.u e'));
    }

    public function testToTime(): void
    {
        $time = DateTime::fromString('2018-12-08 12:45:00.123456')->toTime();

        self::assertInstanceOf(Time::class, $time);
        self::assertSame('1970-01-01 12:45:00.123456 UTC', $time->format('Y-m-d H:i:s.u e'));
    }

    public function testToNative(): void
    {
        $dateTime = DateTime::fromString('2018-12-08 12:45:00.123456', TimeZone::fromString('EET'))->toNative();

        self::assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        self::assertSame('2018-12-08 12:45:00.123456 EET', $dateTime->format('Y-m-d H:i:s.u e'));
    }

    public function testToMutable(): void
    {
        $dateTime = DateTime::fromString('2018-12-08 12:45:00.123456', TimeZone::fromString('EET'))->toMutable();

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame('2018-12-08 12:45:00.123456 EET', $dateTime->format('Y-m-d H:i:s.u e'));
    }

    /** @dataProvider dataToTimestamp */
    public function testToTimestamp(?TimeZone $timeZone): void
    {
        $timestamp = time();
        $dateTime = DateTime::fromTimestamp($timestamp, $timeZone);

        self::assertSame($timestamp, $dateTime->toTimestamp());
    }

    public function dataToTimestamp(): iterable
    {
        return [
            [null],
            [TimeZone::fromString('America/Los_Angeles')],
        ];
    }

    public function testSetState(): void
    {
        $dateTime = DateTime::fromString('2020-07-16 18:30:00.123456', TimeZone::fromString('America/Los_Angeles'));

        $dateTime2 = null;
        eval('$dateTime2 = '.var_export($dateTime, true).';');

        self::assertDateTime('2020-07-16 18:30:00.123456 America/Los_Angeles', $dateTime2);
    }

    public function testSerialization(): void
    {
        $dateTime = DateTime::fromString('2020-07-16 18:30:00.123456', TimeZone::fromString('America/Los_Angeles'));
        $dateTime = unserialize(serialize($dateTime));

        self::assertDateTime('2020-07-16 18:30:00.123456 America/Los_Angeles', $dateTime);
    }

    public function testDebugInfo(): void
    {
        $timeZone = TimeZone::fromString('America/Los_Angeles');
        $dateTime = DateTime::fromString('2020-07-16 18:30:00.123456', $timeZone);

        $expected = [
            'dateTime' => '2020-07-16T18:30:00.123456-07:00',
            'timeZone' => $timeZone,
        ];

        self::assertSame($expected, $dateTime->__debugInfo());
    }

    // === DateTrait & TimeTrait ===

    public function testGetter(): void
    {
        $dateTime = DateTime::fromString('2020-08-06 09:00:30.012340');

        self::assertSame(2020, $dateTime->getYear());
        self::assertSame(8, $dateTime->getMonth());
        self::assertSame(6, $dateTime->getDay());

        self::assertSame(9, $dateTime->getHour());
        self::assertSame(0, $dateTime->getMinute());
        self::assertSame(30, $dateTime->getSecond());
        self::assertSame(12340, $dateTime->getMicrosecond());
    }

    /** @dataProvider dataGetQuarter */
    public function testGetQuarter(int $expected, string $dateTime): void
    {
        $dateTime = DateTime::fromString($dateTime);

        self::assertSame($expected, $dateTime->getQuarter());
    }

    public function dataGetQuarter(): iterable
    {
        return [
            [1, '2020-01-01 00:00:00'],
            [1, '2020-03-31 23:59:59.999999'],
            [2, '2020-04-01 00:00:00'],
            [2, '2020-06-30 23:59:59.999999'],
            [3, '2020-07-01 00:00:00'],
            [3, '2020-09-30 23:59:59.999999'],
            [4, '2020-10-01 00:00:00'],
            [4, '2020-12-31 23:59:59.999999'],
        ];
    }

    /** @dataProvider dataIsFirstDayOfYear */
    public function testIsFirstDayOfYear(bool $expected, string $dateTime): void
    {
        self::assertSame($expected, DateTime::fromString($dateTime)->isFirstDayOfYear());
    }

    public function dataIsFirstDayOfYear(): iterable
    {
        return [
            [true, '2020-01-01 00:00:00'],
            [true, '2021-01-01 23:59:59.999999'],
            [false, '2020-12-31 23:59:59.999999'],
            [false, '2020-02-01 00:00:00'],
            [false, '2020-01-02 00:00:00'],
        ];
    }

    /** @dataProvider dataIsLastDayOfYear */
    public function testIsLastDayOfYear(bool $expected, string $dateTime): void
    {
        self::assertSame($expected, DateTime::fromString($dateTime)->isLastDayOfYear());
    }

    public function dataIsLastDayOfYear(): iterable
    {
        return [
            [true, '2020-12-31 00:00:00'],
            [true, '2021-12-31 23:59:59.999999'],
            [false, '2020-01-01 00:00:00'],
            [false, '2021-12-30 23:59:59.999999'],
            [false, '2020-10-31 00:00:00'],
        ];
    }

    /** @dataProvider dataIsFirstDayOfMonth */
    public function testIsFirstDayOfMonth(bool $expected, string $dateTime): void
    {
        self::assertSame($expected, DateTime::fromString($dateTime)->isFirstDayOfMonth());
    }

    public function dataIsFirstDayOfMonth(): iterable
    {
        return [
            [true, '2020-01-01 00:00:00'],
            [true, '2021-04-01 23:59:59.999999'],
            [false, '2020-10-31 23:59:59.999999'],
            [false, '2020-01-02 00:00:00'],
        ];
    }

    /** @dataProvider dataIsLastDayOfMonth */
    public function testIsLastDayOfMonth(bool $expected, string $dateTime): void
    {
        self::assertSame($expected, DateTime::fromString($dateTime)->isLastDayOfMonth());
    }

    public function dataIsLastDayOfMonth(): iterable
    {
        return [
            [true, '2020-01-31 00:00:00'],
            [true, '2020-02-29 23:59:59.999999'],
            [false, '2020-10-30 23:59:59.999999'],
            [false, '2020-02-28 00:00:00'],
        ];
    }

    /** @dataProvider dataIsMidnight */
    public function testIsMidnight(bool $expected, string $dateTime): void
    {
        self::assertSame($expected, DateTime::fromString($dateTime)->isMidnight());
    }

    public function dataIsMidnight(): iterable
    {
        return [
            [true, '2020-08-10 00:00'],
            [true, '2020-01-01 00:00:00.000000'],
            [false, '2020-08-10 23:59:59.999999'],
            [false, '2020-08-10 00:00:00.000001'],
        ];
    }

    /** @dataProvider dataAddYears */
    public function testAddYears(string $expected, DateTime $dateTime, int $years): void
    {
        self::assertDateTime($expected, $dateTime->addYears($years));
    }

    public function dataAddYears(): iterable
    {
        return [
            ['2022-11-28 17:25:00.000000 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00'), 2],
            ['2019-11-28 17:25:00.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00.0123'), -1],
            ['2023-11-29 22:25:00.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:00', TimeZone::fromString('America/Los_Angeles')), 3],
        ];
    }

    /** @dataProvider dataSubYears */
    public function testSubYears(string $expected, DateTime $dateTime, int $years): void
    {
        self::assertDateTime($expected, $dateTime->subYears($years));
    }

    public function dataSubYears(): iterable
    {
        return [
            ['2018-11-28 17:25:00.000000 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00'), 2],
            ['2021-11-28 17:25:00.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00.0123'), -1],
            ['2017-11-29 22:25:00.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:00', TimeZone::fromString('America/Los_Angeles')), 3],
        ];
    }

    /** @dataProvider dataAddMonths */
    public function testAddMonths(string $expected, DateTime $dateTime, int $months): void
    {
        self::assertDateTime($expected, $dateTime->addMonths($months));
    }

    public function dataAddMonths(): iterable
    {
        return [
            ['2021-01-28 17:25:00.000000 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00'), 2],
            ['2020-10-28 17:25:00.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00.0123'), -1],
            ['2022-03-01 22:25:00.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:00', TimeZone::fromString('America/Los_Angeles')), 15],
        ];
    }

    /** @dataProvider dataSubMonths */
    public function testSubMonths(string $expected, DateTime $dateTime, int $months): void
    {
        self::assertDateTime($expected, $dateTime->subMonths($months));
    }

    public function dataSubMonths(): iterable
    {
        return [
            ['2019-11-28 17:25:00.000000 Europe/Berlin', DateTime::fromString('2020-01-28 17:25:00'), 2],
            ['2020-12-28 17:25:00.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00.0123'), -1],
            ['2019-08-29 22:25:00.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:00', TimeZone::fromString('America/Los_Angeles')), 15],
        ];
    }

    /** @dataProvider dataAddWeeks */
    public function testAddWeeks(string $expected, DateTime $dateTime, int $weeks): void
    {
        self::assertDateTime($expected, $dateTime->addWeeks($weeks));
    }

    public function dataAddWeeks(): iterable
    {
        return [
            ['2020-12-12 17:25:00.000000 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00'), 2],
            ['2020-11-21 17:25:00.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00.0123'), -1],
            ['2021-01-03 22:25:00.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:00', TimeZone::fromString('America/Los_Angeles')), 5],
        ];
    }

    /** @dataProvider dataSubWeeks */
    public function testSubWeeks(string $expected, DateTime $dateTime, int $weeks): void
    {
        self::assertDateTime($expected, $dateTime->subWeeks($weeks));
    }

    public function dataSubWeeks(): iterable
    {
        return [
            ['2020-11-14 17:25:00.000000 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00'), 2],
            ['2020-12-05 17:25:00.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00.0123'), -1],
            ['2020-10-25 22:25:00.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:00', TimeZone::fromString('America/Los_Angeles')), 5],
        ];
    }

    /** @dataProvider dataAddDays */
    public function testAddDays(string $expected, DateTime $dateTime, int $days): void
    {
        self::assertDateTime($expected, $dateTime->addDays($days));
    }

    public function dataAddDays(): iterable
    {
        return [
            ['2020-12-01 17:25:00.000000 Europe/Berlin', DateTime::fromString('2020-11-29 17:25:00'), 2],
            ['2020-11-27 17:25:00.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00.0123'), -1],
            ['2021-01-03 22:25:00.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:00', TimeZone::fromString('America/Los_Angeles')), 35],
        ];
    }

    /** @dataProvider dataSubDays */
    public function testSubDays(string $expected, DateTime $dateTime, int $days): void
    {
        self::assertDateTime($expected, $dateTime->subDays($days));
    }

    public function dataSubDays(): iterable
    {
        return [
            ['2020-11-27 17:25:00.000000 Europe/Berlin', DateTime::fromString('2020-11-29 17:25:00'), 2],
            ['2020-12-01 17:25:00.012300 Europe/Berlin', DateTime::fromString('2020-11-30 17:25:00.0123'), -1],
            ['2020-11-29 22:25:00.000000 America/Los_Angeles', DateTime::fromString('2021-01-03 22:25:00', TimeZone::fromString('America/Los_Angeles')), 35],
        ];
    }

    /** @dataProvider dataAddHours */
    public function testAddHours(string $expected, DateTime $dateTime, int $hours): void
    {
        self::assertDateTime($expected, $dateTime->addHours($hours));
    }

    public function dataAddHours(): iterable
    {
        return [
            ['2020-11-28 19:25:00.000000 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00'), 2],
            ['2020-11-28 16:25:00.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00.0123'), -1],
            ['2020-12-01 01:25:00.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:00', TimeZone::fromString('America/Los_Angeles')), 27],
        ];
    }

    /** @dataProvider dataSubHours */
    public function testSubHours(string $expected, DateTime $dateTime, int $hours): void
    {
        self::assertDateTime($expected, $dateTime->subHours($hours));
    }

    public function dataSubHours(): iterable
    {
        return [
            ['2020-11-28 15:25:00.000000 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00'), 2],
            ['2020-11-28 18:25:00.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00.0123'), -1],
            ['2020-11-29 22:25:00.000000 America/Los_Angeles', DateTime::fromString('2020-12-01 01:25:00', TimeZone::fromString('America/Los_Angeles')), 27],
        ];
    }

    /** @dataProvider dataAddMinutes */
    public function testAddMinutes(string $expected, DateTime $dateTime, int $minutes): void
    {
        self::assertDateTime($expected, $dateTime->addMinutes($minutes));
    }

    public function dataAddMinutes(): iterable
    {
        return [
            ['2020-11-28 17:27:00.000000 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00'), 2],
            ['2020-11-28 17:24:00.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00.0123'), -1],
            ['2020-11-29 23:38:00.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:00', TimeZone::fromString('America/Los_Angeles')), 73],
        ];
    }

    /** @dataProvider dataSubMinutes */
    public function testSubMinutes(string $expected, DateTime $dateTime, int $minutes): void
    {
        self::assertDateTime($expected, $dateTime->subMinutes($minutes));
    }

    public function dataSubMinutes(): iterable
    {
        return [
            ['2020-11-28 17:23:00.000000 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00'), 2],
            ['2020-11-28 17:26:00.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:00.0123'), -1],
            ['2020-11-29 21:12:00.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:00', TimeZone::fromString('America/Los_Angeles')), 73],
        ];
    }

    /** @dataProvider dataAddSeconds */
    public function testAddSeconds(string $expected, DateTime $dateTime, int $seconds): void
    {
        self::assertDateTime($expected, $dateTime->addSeconds($seconds));
    }

    public function dataAddSeconds(): iterable
    {
        return [
            ['2020-11-28 17:25:07.000000 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:05'), 2],
            ['2020-11-28 17:25:04.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:05.0123'), -1],
            ['2020-11-29 22:26:18.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:05', TimeZone::fromString('America/Los_Angeles')), 73],
        ];
    }

    /** @dataProvider dataSubSeconds */
    public function testSubSeconds(string $expected, DateTime $dateTime, int $seconds): void
    {
        self::assertDateTime($expected, $dateTime->subSeconds($seconds));
    }

    public function dataSubSeconds(): iterable
    {
        return [
            ['2020-11-28 17:25:03.000000 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:05'), 2],
            ['2020-11-28 17:25:06.012300 Europe/Berlin', DateTime::fromString('2020-11-28 17:25:05.0123'), -1],
            ['2020-11-29 22:23:52.000000 America/Los_Angeles', DateTime::fromString('2020-11-29 22:25:05', TimeZone::fromString('America/Los_Angeles')), 73],
        ];
    }

    /** @dataProvider dataToFirstDayOfYear */
    public function testToFirstDayOfYear(string $expected, DateTime $dateTime): void
    {
        self::assertDateTime($expected, $dateTime->toFirstDayOfYear());
    }

    public function dataToFirstDayOfYear(): iterable
    {
        return [
            ['2019-01-01 00:00:00.000000 Europe/Berlin', DateTime::fromString('2019-01-01 00:00:00')],
            ['2020-01-01 23:59:59.999999 Europe/Berlin', DateTime::fromString('2020-12-31 23:59:59.999999')],
            ['2020-01-01 22:25:05.000000 America/Los_Angeles', DateTime::fromString('2020-05-29 22:25:05', TimeZone::fromString('America/Los_Angeles'))],
        ];
    }

    /** @dataProvider dataToLastDayOfYear */
    public function testToLastDayOfYear(string $expected, DateTime $dateTime): void
    {
        self::assertDateTime($expected, $dateTime->toLastDayOfYear());
    }

    public function dataToLastDayOfYear(): iterable
    {
        return [
            ['2019-12-31 00:00:00.000000 Europe/Berlin', DateTime::fromString('2019-01-01 00:00:00')],
            ['2020-12-31 23:59:59.999999 Europe/Berlin', DateTime::fromString('2020-12-31 23:59:59.999999')],
            ['2020-12-31 22:25:05.000000 America/Los_Angeles', DateTime::fromString('2020-05-29 22:25:05', TimeZone::fromString('America/Los_Angeles'))],
        ];
    }

    /** @dataProvider dataToFirstDayOfQuarter */
    public function testToFirstDayOfQuarter(string $expected, DateTime $dateTime): void
    {
        self::assertDateTime($expected, $dateTime->toFirstDayOfQuarter());
    }

    public function dataToFirstDayOfQuarter(): iterable
    {
        return [
            ['2019-01-01 00:00:00.000000 Europe/Berlin', DateTime::fromString('2019-01-01 00:00:00')],
            ['2020-01-01 23:59:59.999999 Europe/Berlin', DateTime::fromString('2020-03-31 23:59:59.999999')],
            ['2020-07-01 23:59:59.999999 Europe/Berlin', DateTime::fromString('2020-08-15 23:59:59.999999')],
            ['2020-04-01 22:25:05.000000 America/Los_Angeles', DateTime::fromString('2020-05-29 22:25:05', TimeZone::fromString('America/Los_Angeles'))],
        ];
    }

    /** @dataProvider dataToLastDayOfQuarter */
    public function testToLastDayOfQuarter(string $expected, DateTime $dateTime): void
    {
        self::assertDateTime($expected, $dateTime->toLastDayOfQuarter());
    }

    public function dataToLastDayOfQuarter(): iterable
    {
        return [
            ['2019-03-31 00:00:00.000000 Europe/Berlin', DateTime::fromString('2019-01-01 00:00:00')],
            ['2020-03-31 23:59:59.999999 Europe/Berlin', DateTime::fromString('2020-03-31 23:59:59.999999')],
            ['2020-09-30 23:59:59.999999 Europe/Berlin', DateTime::fromString('2020-08-15 23:59:59.999999')],
            ['2020-06-30 22:25:05.000000 America/Los_Angeles', DateTime::fromString('2020-05-29 22:25:05', TimeZone::fromString('America/Los_Angeles'))],
        ];
    }

    /** @dataProvider dataToFirstDayOfMonth */
    public function testToFirstDayOfMonth(string $expected, DateTime $dateTime): void
    {
        self::assertDateTime($expected, $dateTime->toFirstDayOfMonth());
    }

    public function dataToFirstDayOfMonth(): iterable
    {
        return [
            ['2019-01-01 00:00:00.000000 Europe/Berlin', DateTime::fromString('2019-01-01 00:00:00')],
            ['2020-03-01 23:59:59.999999 Europe/Berlin', DateTime::fromString('2020-03-31 23:59:59.999999')],
            ['2020-05-01 22:25:05.000000 America/Los_Angeles', DateTime::fromString('2020-05-29 22:25:05', TimeZone::fromString('America/Los_Angeles'))],
        ];
    }

    /** @dataProvider dataToLastDayOfMonth */
    public function testToLastDayOfMonth(string $expected, DateTime $dateTime): void
    {
        self::assertDateTime($expected, $dateTime->toLastDayOfMonth());
    }

    public function dataToLastDayOfMonth(): iterable
    {
        return [
            ['2019-01-31 00:00:00.000000 Europe/Berlin', DateTime::fromString('2019-01-01 00:00:00')],
            ['2020-03-31 23:59:59.999999 Europe/Berlin', DateTime::fromString('2020-03-31 23:59:59.999999')],
            ['2020-02-29 22:25:05.000000 America/Los_Angeles', DateTime::fromString('2020-02-15 22:25:05', TimeZone::fromString('America/Los_Angeles'))],
        ];
    }

    /** @dataProvider dataToMonth */
    public function testToMonth(Month $expected, string $dateTime): void
    {
        $dateTime = DateTime::fromString($dateTime);

        self::assertSame($expected, $dateTime->toMonth());
    }

    public function dataToMonth(): iterable
    {
        return [
            [Month::March, '2019-03-12 12:05:23'],
            [Month::February, '2020-02-29 23:59:59'],
            [Month::July, '2020-07-01 00:00:00'],
        ];
    }

    /** @dataProvider dataToWeekday */
    public function testToWeekday(Weekday $expected, string $dateTime): void
    {
        $dateTime = DateTime::fromString($dateTime);

        self::assertSame($expected, $dateTime->toWeekday());
    }

    public function dataToWeekday(): iterable
    {
        return [
            [Weekday::Monday, '2019-07-15 12:05:23'],
            [Weekday::Thursday, '2020-12-31 00:00:00'],
            [Weekday::Sunday, '2021-01-03 12:05:23'],
        ];
    }

    /** @dataProvider dataToPrevWeekday */
    public function testToPrevWeekday(string $expected, string $dateTime, $weekday): void
    {
        $dateTime = DateTime::fromString($dateTime);

        self::assertDateTime($expected, $dateTime->toPrevWeekday($weekday));
    }

    public function dataToPrevWeekday(): iterable
    {
        return [
            ['2019-07-15 12:05:23.000000 Europe/Berlin', '2019-07-15 12:05:23', Weekday::Monday],
            ['2019-07-14 12:05:23.000000 Europe/Berlin', '2019-07-15 12:05:23', Weekday::Sunday],
            ['2020-12-26 00:00:00.000000 Europe/Berlin', '2021-01-01 00:00:00', Weekday::Saturday],
        ];
    }

    /** @dataProvider dataToNextWeekday */
    public function testToNextWeekday(string $expected, string $dateTime, $weekday): void
    {
        $dateTime = DateTime::fromString($dateTime);

        self::assertDateTime($expected, $dateTime->toNextWeekday($weekday));
    }

    public function dataToNextWeekday(): iterable
    {
        return [
            ['2019-07-14 12:05:23.000000 Europe/Berlin', '2019-07-14 12:05:23', Weekday::Sunday],
            ['2019-07-15 12:05:23.000000 Europe/Berlin', '2019-07-14 12:05:23', Weekday::Monday],
            ['2021-01-01 00:00:00.000000 Europe/Berlin', '2020-12-26 00:00:00', Weekday::Friday],
        ];
    }

    // === CompareTrait ===

    /** @dataProvider dataMin */
    public function testMin(int $expectedIndex, DateTime ...$dateTimes): void
    {
        self::assertSame($dateTimes[$expectedIndex], DateTime::min(...$dateTimes));
    }

    public function dataMin(): iterable
    {
        return [
            [0, DateTime::fromString('2020-07-19 18:30:00')],
            [0, DateTime::fromString('2020-07-19 18:30:00.000001'), DateTime::fromString('2020-07-19 18:30:00.000002')],
            [1, DateTime::now(), DateTime::fromString('2020-07-19 18:30:00'), DateTime::fromString('2020-07-20 17:00:00')],
            [1, DateTime::utcFromString('2020-07-19 18:00'), DateTime::fromString('2020-07-19 18:00')],
        ];
    }

    /** @dataProvider dataMax */
    public function testMax(int $expectedIndex, DateTime ...$dateTimes): void
    {
        self::assertSame($dateTimes[$expectedIndex], DateTime::max(...$dateTimes));
    }

    public function dataMax(): iterable
    {
        return [
            [0, DateTime::fromString('2020-07-19 18:30:00')],
            [0, DateTime::fromString('2020-07-19 18:30:00.000002'), DateTime::fromString('2020-07-19 18:30:00.000001')],
            [1, DateTime::fromString('2018-12-31'), DateTime::fromString('2020-07-19 18:30:00'), DateTime::fromString('2020-07-18 19:00:00')],
            [0, DateTime::utcFromString('2020-07-19 18:00'), DateTime::fromString('2020-07-19 18:00')],
        ];
    }

    /** @dataProvider dataDiff */
    public function testDiff(string $expected, DateTime $dateTime, DateTime $other, ...$parameters): void
    {
        $duration = $dateTime->diff($other, ...$parameters);

        self::assertInstanceOf(Duration::class, $duration);
        self::assertSame($expected, $duration->toIso());
    }

    public function dataDiff(): iterable
    {
        return [
            ['PT0S', DateTime::fromString('2020-07-19 18:30:00'), DateTime::fromString('2020-07-19 18:30:00')],
            ['PT0S', DateTime::fromString('2020-07-19 18:30:00'), DateTime::utcFromString('2020-07-19 16:30:00')],
            ['P1Y3DT5H29M59.876544S', DateTime::fromString('2020-07-19 18:30:00.123456'), DateTime::fromString('2021-07-23 00:00:00')],
            ['-P1DT23H', DateTime::fromString('2020-07-19 18:30:00'), DateTime::fromString('2020-07-17 19:30:00')],
            ['P1DT23H', DateTime::fromString('2020-07-19 18:30:00'), DateTime::fromString('2020-07-17 19:30:00'), true],
            ['-PT1H', DateTime::utcFromString('2020-07-19 17:30'), DateTime::fromString('2020-07-19 18:30'), false],
        ];
    }

    /** @dataProvider dataCompareTo */
    public function testCompareTo(int $expected, DateTime $dateTime, DateTime $other): void
    {
        self::assertSame($expected, $dateTime->compareTo($other));
        self::assertSame(0 === $expected, $dateTime->equals($other));
        self::assertSame(1 === $expected, $dateTime->greaterThan($other));
        self::assertSame(-1 !== $expected, $dateTime->greaterEquals($other));
        self::assertSame(-1 === $expected, $dateTime->lowerThan($other));
        self::assertSame(1 !== $expected, $dateTime->lowerEquals($other));
    }

    public function dataCompareTo(): iterable
    {
        return [
            [0, DateTime::fromString('2020-07-19 18:30:00'), DateTime::fromString('2020-07-19 18:30:00')],
            [0, DateTime::fromString('2020-07-19 18:30:00'), DateTime::utcFromString('2020-07-19 16:30:00')],
            [-1, DateTime::fromString('2020-07-19 18:30:00.123456'), DateTime::fromString('2021-07-23 00:00:00')],
            [1, DateTime::fromString('2020-07-19 18:30:00'), DateTime::fromString('2020-07-18 18:30:00')],
            [1, DateTime::utcFromString('2020-07-19 17:30'), DateTime::fromString('2020-07-19 18:30')],
        ];
    }

    // === FormatTrait ===

    public function testJsonSerialize(): void
    {
        $dateTime = DateTime::fromString('2020-07-26 18:30:00.123456');

        self::assertJsonStringEqualsJsonString('"2020-07-26T18:30:00.123456+02:00"', json_encode($dateTime));
    }

    // === ModifyTrait ===

    /** @dataProvider dataModify */
    public function testModify(string $expected, string $modify): void
    {
        $dateTime = DateTime::fromString('2020-07-26 18:30:00.123456');

        self::assertDateTime($expected, $dateTime->modify($modify));
    }

    public function dataModify(): iterable
    {
        return [
            ['2020-07-27 13:30:00.123456 Europe/Berlin', '+1day -5hours'],
            ['2020-07-26 10:00:00.000000 Europe/Berlin', '10:00'],
            ['2020-07-01 18:30:00.123456 Europe/Berlin', 'first day of this month'],
        ];
    }

    /** @dataProvider dataAdd */
    public function testAdd(string $expected, string $duration): void
    {
        $dateTime = DateTime::fromString('2020-07-26 18:30:00.123456');

        self::assertDateTime($expected, $dateTime->add(Duration::fromString($duration)));
    }

    public function dataAdd(): iterable
    {
        return [
            ['2020-08-02 16:30:05.456456 Europe/Berlin', 'P1WT-2H5.333S'],
            ['2019-07-26 18:28:00.123456 Europe/Berlin', '-P1YT2M'],
        ];
    }

    /** @dataProvider dataSub */
    public function testSub(string $expected, string $duration): void
    {
        $dateTime = DateTime::fromString('2020-07-26 18:30:00.123456');

        self::assertDateTime($expected, $dateTime->sub(Duration::fromString($duration)));
    }

    public function dataSub(): iterable
    {
        return [
            ['2020-07-19 20:29:55.000456 Europe/Berlin', 'P1WT-2H5.123S'],
            ['2021-07-26 18:32:00.123456 Europe/Berlin', '-P1YT2M'],
        ];
    }

    // === Assertions ===

    /**
     * @param DateTime $dateTime
     */
    private static function assertDateTime(string $expected, $dateTime): void
    {
        self::assertInstanceOf(DateTime::class, $dateTime);
        self::assertSame($expected, $dateTime->format('Y-m-d H:i:s.u e'));
    }

    /**
     * @param DateTime $dateTime
     */
    private static function assertDateTimeNow($dateTime, string $expectedTimeZone = 'Europe/Berlin'): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($expectedTimeZone));

        self::assertInstanceOf(DateTime::class, $dateTime);

        self::assertThat($dateTime->format('Y-m-d H:i:s'), self::logicalOr(
            self::identicalTo($now->modify('-1sec')->format('Y-m-d H:i:s')),
            self::identicalTo($now->format('Y-m-d H:i:s'))
        ));

        self::assertSame($expectedTimeZone, $dateTime->getTimeZone()->getName());
    }
}
