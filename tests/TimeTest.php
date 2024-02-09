<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Tests;

use HillValley\Fluxcap\Base\IntlFormatter;
use HillValley\Fluxcap\DateTime;
use HillValley\Fluxcap\Duration;
use HillValley\Fluxcap\Exception\FormatMismatchException;
use HillValley\Fluxcap\Exception\InvalidPartException;
use HillValley\Fluxcap\Exception\InvalidStringException;
use HillValley\Fluxcap\Time;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(IntlFormatter::class)]
#[CoversClass(Time::class)]
final class TimeTest extends TestCase
{
    public function testConstruct(): void
    {
        $method = new \ReflectionMethod(Time::class, '__construct');

        self::assertTrue($method->isPrivate());
    }

    public function testNow(): void
    {
        self::assertTimeNow(Time::now());
    }

    public function testFromStringNow(): void
    {
        self::assertTimeNow(Time::fromString('now'));
    }

    #[DataProvider('dataFromString')]
    public function testFromString(string $expected, string $time): void
    {
        self::assertTime($expected, Time::fromString($time));
    }

    public static function dataFromString(): iterable
    {
        return [
            ['15:32:00.000000', '15:32'],
            ['22:07:02.000000', '22:07:02'],
            ['09:13:45.439821', '09:13:45.439821'],
            ['22:07:02.000000', '@'.strtotime('2018-09-08 22:07:02')],
            ['03:30:00.000000', '17:30 +10hours'],
            ['23:55:00.000000', '00:00 -5minutes'],
        ];
    }

    #[DataProvider('dataFromStringInvalid')]
    public function testFromStringInvalid(string $expected, string $time): void
    {
        $this->expectException(InvalidStringException::class);
        $this->expectExceptionMessage($expected);

        Time::fromString($time);
    }

    public static function dataFromStringInvalid(): iterable
    {
        return [
            ['The time string can not be empty (use "now" for current time).', ''],
            ['Failed to parse time string (foo) at position 0 (f): The timezone could not be found in the database.', 'foo'],
            ['Failed to parse time string (25:00) at position 0 (2): Unexpected character.', '25:00'],
        ];
    }

    #[DataProvider('dataFromFormat')]
    public function testFromFormat(string $expected, string $format, string $time): void
    {
        self::assertTime($expected, Time::fromFormat($format, $time));
    }

    public static function dataFromFormat(): iterable
    {
        return [
            ['15:32:00.000000', 'i.H', '32.15'],
            ['09:13:45.123000', 'u s i G', '123 45 13 9'],
        ];
    }

    public function testFromFormatInvalid(): void
    {
        $this->expectException(FormatMismatchException::class);

        Time::fromFormat('H.i', '20:10');
    }

    #[DataProvider('dataFromParts')]
    public function testFromParts(string $expected, ...$parts): void
    {
        self::assertTime($expected, Time::fromParts(...$parts));
    }

    public static function dataFromParts(): iterable
    {
        return [
            ['05:00:00.000000', 5],
            ['11:43:00.000000', 11, 43],
            ['06:12:50.000000', 6, 12, 50],
            ['00:05:13.000192', 0, 5, 13, 192],
        ];
    }

    #[DataProvider('dataFromPartsInvalid')]
    public function testFromPartsInvalid(string $expected, ...$parts): void
    {
        $this->expectException(InvalidPartException::class);
        $this->expectExceptionMessage($expected);

        Time::fromParts(...$parts);
    }

    public static function dataFromPartsInvalid(): iterable
    {
        return [
            ['Hour part must be between 0 and 23, but -1 given.', -1],
            ['Hour part must be between 0 and 23, but 24 given.', 24],
            ['Minute part must be between 0 and 59, but -1 given.', 13, -1],
            ['Minute part must be between 0 and 59, but 60 given.', 0, 60],
            ['Seconds part must be between 0 and 59, but -1 given.', 7, 45, -1],
            ['Seconds part must be between 0 and 59, but 60 given.', 7, 0, 60],
            ['Microseconds part must be between 0 and 999999, but -1 given.', 7, 25, 40, -1],
            ['Microseconds part must be between 0 and 999999, but 1000000 given.', 7, 25, 0, 1000000],
        ];
    }

    public function testFromTimestamp(): void
    {
        $timestamp = strtotime('01:05:20');
        self::assertTime('01:05:20.000000', Time::fromTimestamp($timestamp));
    }

    #[DataProvider('dataFromNative')]
    public function testFromNative(string $expected, \DateTimeInterface $time): void
    {
        self::assertTime($expected, Time::fromNative($time));
    }

    public static function dataFromNative(): iterable
    {
        return [
            ['23:00:00.000000', new \DateTime('2018-09-13 23:00:00')],
            ['00:15:00.123000', new \DateTimeImmutable('2018-09-14 00:15:00.123000')],
            ['23:45:00.000000', new \DateTimeImmutable('2018-09-15 23:45:00', new \DateTimeZone('UTC'))],
        ];
    }

    #[DataProvider('dataCast')]
    public function testCast(string $expected, $time): void
    {
        self::assertTime($expected, Time::cast($time));
    }

    public static function dataCast(): iterable
    {
        return [
            ['23:35:00.000000', strtotime('2018-06-17 23:35:00')],
            ['01:00:45.768123', '2018-06-03 01:00:45.768123'],
            ['00:15:00.000000', new \DateTimeImmutable('2018-09-14 00:15:00')],
            ['05:30:00.000000', Time::fromString('05:30:00')],
            ['00:25:30.000000', DateTime::fromString('00:25:30')],
        ];
    }

    #[DataProvider('dataToIso')]
    public function testToIso(string $expected, string $time): void
    {
        $time = Time::fromString($time);

        self::assertSame($expected, $time->toIso());
        self::assertSame($expected, (string) $time);
    }

    public static function dataToIso(): iterable
    {
        return [
            ['10:00:00.000000', '10:00'],
            ['10:25:08.000000', '10:25:08.000000'],
            ['10:25:08.100000', '10:25:08.100000'],
            ['10:25:08.000123', '10:25:08.000123'],
        ];
    }

    public function testFormatIntl(): void
    {
        $time = Time::fromString('03:20:50.123456');
        self::assertSame('03:20:50', $time->formatIntl());
        self::assertSame('03:20', $time->formatIntl(\IntlDateFormatter::SHORT));
        self::assertSame('1.01.1970 3:20:50.1230 UTC', $time->formatIntl(null, 'd.MM.yyyy H:mm:ss.SSSS VV'));
    }

    public function testToDateTime(): void
    {
        $dateTime = Time::fromString('12:45:00.123456')->toDateTime();

        self::assertInstanceOf(DateTime::class, $dateTime);
        self::assertSame('1970-01-01 12:45:00.123456 Europe/Berlin', $dateTime->format('Y-m-d H:i:s.u e'));
    }

    public function testToNative(): void
    {
        $dateTime = Time::fromString('12:45:00.123456')->toNative();

        self::assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        self::assertSame('1970-01-01 12:45:00.123456 Europe/Berlin', $dateTime->format('Y-m-d H:i:s.u e'));
    }

    public function testToMutable(): void
    {
        $dateTime = Time::fromString('12:45:00.123456')->toMutable();

        self::assertInstanceOf(\DateTime::class, $dateTime);
        self::assertSame('1970-01-01 12:45:00.123456 Europe/Berlin', $dateTime->format('Y-m-d H:i:s.u e'));
    }

    public function testToTimestamp(): void
    {
        $string = '17:30:08';
        $time = Time::fromTimestamp(strtotime('2020-07-16 '.$string));

        self::assertSame(strtotime('1970-01-01 '.$string), $time->toTimestamp());
    }

    public function testSetState(): void
    {
        $timeString = '18:30:00.123456';
        $time = null;
        eval('$time = '.var_export(Time::fromString($timeString), true).';');

        self::assertTime($timeString, $time);
    }

    public function testSerialization(): void
    {
        $timeString = '18:30:00.123456';

        self::assertTime($timeString, unserialize(serialize(Time::fromString($timeString))));
    }

    public function testDebugInfo(): void
    {
        $time = Time::fromString('18:30:00.123456');

        $expected = [
            'time' => '18:30:00.123456',
        ];

        self::assertSame($expected, $time->__debugInfo());
    }

    // === TimeTrait ===

    public function testGetter(): void
    {
        $time = Time::fromString('09:00:30.012340');

        self::assertSame(9, $time->getHour());
        self::assertSame(0, $time->getMinute());
        self::assertSame(30, $time->getSecond());
        self::assertSame(12340, $time->getMicrosecond());
    }

    #[DataProvider('dataIsMidnight')]
    public function testIsMidnight(bool $expected, string $time): void
    {
        self::assertSame($expected, Time::fromString($time)->isMidnight());
    }

    public static function dataIsMidnight(): iterable
    {
        return [
            [true, '00:00'],
            [true, '00:00:00.000000'],
            [false, '23:59:59.999999'],
            [false, '00:00:00.000001'],
        ];
    }

    #[DataProvider('dataAddHours')]
    public function testAddHours(string $expected, Time $time, int $hours): void
    {
        self::assertTime($expected, $time->addHours($hours));
    }

    public static function dataAddHours(): iterable
    {
        return [
            ['19:25:00.000000', Time::fromString('17:25:00'), 2],
            ['16:25:00.012300', Time::fromString('17:25:00.0123'), -1],
            ['01:25:00.000000', Time::fromString('22:25:00'), 27],
        ];
    }

    #[DataProvider('dataSubHours')]
    public function testSubHours(string $expected, Time $time, int $hours): void
    {
        self::assertTime($expected, $time->subHours($hours));
    }

    public static function dataSubHours(): iterable
    {
        return [
            ['15:25:00.000000', Time::fromString('17:25:00'), 2],
            ['18:25:00.012300', Time::fromString('17:25:00.0123'), -1],
            ['22:25:00.000000', Time::fromString('01:25:00'), 27],
        ];
    }

    #[DataProvider('dataAddMinutes')]
    public function testAddMinutes(string $expected, Time $time, int $minutes): void
    {
        self::assertTime($expected, $time->addMinutes($minutes));
    }

    public static function dataAddMinutes(): iterable
    {
        return [
            ['17:27:00.000000', Time::fromString('17:25:00'), 2],
            ['17:24:00.012300', Time::fromString('17:25:00.0123'), -1],
            ['23:38:00.000000', Time::fromString('22:25:00'), 73],
        ];
    }

    #[DataProvider('dataSubMinutes')]
    public function testSubMinutes(string $expected, Time $time, int $minutes): void
    {
        self::assertTime($expected, $time->subMinutes($minutes));
    }

    public static function dataSubMinutes(): iterable
    {
        return [
            ['17:23:00.000000', Time::fromString('17:25:00'), 2],
            ['17:26:00.012300', Time::fromString('17:25:00.0123'), -1],
            ['21:12:00.000000', Time::fromString('22:25:00'), 73],
        ];
    }

    #[DataProvider('dataAddSeconds')]
    public function testAddSeconds(string $expected, Time $time, int $seconds): void
    {
        self::assertTime($expected, $time->addSeconds($seconds));
    }

    public static function dataAddSeconds(): iterable
    {
        return [
            ['17:25:07.000000', Time::fromString('17:25:05'), 2],
            ['17:25:04.012300', Time::fromString('17:25:05.0123'), -1],
            ['22:26:18.000000', Time::fromString('22:25:05'), 73],
        ];
    }

    #[DataProvider('dataSubSeconds')]
    public function testSubSeconds(string $expected, Time $time, int $seconds): void
    {
        self::assertTime($expected, $time->subSeconds($seconds));
    }

    public static function dataSubSeconds(): iterable
    {
        return [
            ['17:25:03.000000', Time::fromString('17:25:05'), 2],
            ['17:25:06.012300', Time::fromString('17:25:05.0123'), -1],
            ['22:23:52.000000', Time::fromString('22:25:05'), 73],
        ];
    }

    // === CompareTrait ===

    #[DataProvider('dataMin')]
    public function testMin(int $expectedIndex, Time ...$times): void
    {
        self::assertSame($times[$expectedIndex], Time::min(...$times));
    }

    public static function dataMin(): iterable
    {
        return [
            [0, Time::fromString('18:30:00')],
            [0, Time::fromString('18:30:00.000001'), Time::fromString('18:30:00.000002')],
            [1, Time::fromString('18:35'), Time::fromString('18:30:00'), Time::fromString('20:00:00')],
        ];
    }

    #[DataProvider('dataMax')]
    public function testMax(int $expectedIndex, Time ...$times): void
    {
        self::assertSame($times[$expectedIndex], Time::max(...$times));
    }

    public static function dataMax(): iterable
    {
        return [
            [0, Time::fromString('18:30:00')],
            [0, Time::fromString('18:30:00.000002'), Time::fromString('18:30:00.000001')],
            [1, Time::fromString('18:10'), Time::fromString('18:30:00'), Time::fromString('00:10:45')],
        ];
    }

    #[DataProvider('dataDiff')]
    public function testDiff(string $expected, Time $dateTime, Time $other, ...$parameters): void
    {
        $duration = $dateTime->diff($other, ...$parameters);

        self::assertInstanceOf(Duration::class, $duration);
        self::assertSame($expected, $duration->toIso());
    }

    public static function dataDiff(): iterable
    {
        return [
            ['PT0S', Time::fromString('18:30:00'), Time::fromString('18:30:00')],
            ['PT4H29M59.876544S', Time::fromString('18:30:00.123456'), Time::fromString('23:00:00')],
            ['-PT2H30M', Time::fromString('18:30:00'), Time::fromString('16:00:00')],
            ['-PT23H59M', Time::fromString('23:59'), Time::fromString('00:00')],
            ['PT2H30M', Time::fromString('18:30:00'), Time::fromString('16:00:00'), true],
        ];
    }

    #[DataProvider('dataCompareTo')]
    public function testCompareTo(int $expected, Time $dateTime, Time $other): void
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
            [0, Time::fromString('18:30:00'), Time::fromString('18:30:00')],
            [-1, Time::fromString('18:30:00.123456'), Time::fromString('23:00')],
            [1, Time::fromString('18:30:00'), Time::fromString('16:00:00')],
            [1, Time::fromString('23:59'), Time::fromString('00:00')],
        ];
    }

    // === FormatTrait ===

    public function testJsonSerialize(): void
    {
        $time = Time::fromString('18:30:00.123456');

        self::assertJsonStringEqualsJsonString('"18:30:00.123456"', json_encode($time));
    }

    // === ModifyTrait ===

    #[DataProvider('dataModify')]
    public function testModify(string $expected, string $modify): void
    {
        $time = Time::fromString('18:30:00.123456');

        self::assertTime($expected, $time->modify($modify));
    }

    public static function dataModify(): iterable
    {
        return [
            ['19:25:00.123456', '+1hour -5min'],
            ['18:30:00.123456', '+2days'],
            ['20:30:00.123456', '+1days +2hours'],
        ];
    }

    public function testModifyInvalid(): void
    {
        $this->expectException(InvalidStringException::class);
        $this->expectExceptionMessageMatches('/^Failed to parse time string \(foo\)/');

        Time::fromString('13:00')->modify('foo');
    }

    #[DataProvider('dataAdd')]
    public function testAdd(string $expected, string $duration): void
    {
        $dateTime = Time::fromString('18:30:00.123456');

        self::assertTime($expected, $dateTime->add(Duration::fromString($duration)));
    }

    public static function dataAdd(): iterable
    {
        return [
            ['16:30:05.456456', 'P1WT-2H5.333S'],
            ['18:28:00.123456', '-P1YT2M'],
        ];
    }

    #[DataProvider('dataSub')]
    public function testSub(string $expected, string $duration): void
    {
        $dateTime = Time::fromString('18:30:00.123456');

        self::assertTime($expected, $dateTime->sub(Duration::fromString($duration)));
    }

    public static function dataSub(): iterable
    {
        return [
            ['20:29:55.000456', 'P1WT-2H5.123S'],
            ['18:32:00.123456', '-P1YT2M'],
        ];
    }

    // === Assertions ===

    /**
     * @param Time $time
     */
    private static function assertTime(string $expected, $time): void
    {
        self::assertInstanceOf(Time::class, $time);
        self::assertSame('1970-01-01 UTC', $time->format('Y-m-d e'));
        self::assertSame($expected, $time->format('H:i:s.u'));
    }

    /**
     * @param Time $time
     */
    private static function assertTimeNow($time): void
    {
        $now = time();

        self::assertInstanceOf(Time::class, $time);
        self::assertSame('1970-01-01 UTC', $time->format('Y-m-d e'));

        self::assertThat($time->format('H:i:s'), self::logicalOr(
            self::identicalTo(date('H:i:s', $now - 1)),
            self::identicalTo(date('H:i:s', $now)),
        ));
    }
}
