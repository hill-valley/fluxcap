<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Tests;

use HillValley\Fluxcap\Date;
use HillValley\Fluxcap\DateTime;
use HillValley\Fluxcap\Duration;
use HillValley\Fluxcap\Exception\InvalidStringException;
use HillValley\Fluxcap\TimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Duration::class)]
final class DurationTest extends TestCase
{
    public function testConstruct(): void
    {
        $method = new \ReflectionMethod(Duration::class, '__construct');

        self::assertTrue($method->isPrivate());
    }

    #[DataProvider('dataFromString')]
    public function testFromString(string $expected, ?string $duration = null): void
    {
        static::assertDuration($expected, Duration::fromString($duration ?? $expected));
    }

    public static function dataFromString(): iterable
    {
        return [
            ['PT0S', 'P0D'],
            ['P1DT20M'],
            ['-P1DT20M'],
            ['P21D', 'P3W'],
            ['P-1Y1M'],
            ['PT1H5.123S'],
            ['PT0.000123S'],
            ['PT-2.123456S'],
        ];
    }

    public function testFromStringInvalid(): void
    {
        $this->expectException(InvalidStringException::class);
        $this->expectExceptionMessage('The string "foo" is not a valid duration string.');

        Duration::fromString('foo');
    }

    public function testFromParts(): void
    {
        $duration = Duration::fromParts(0, 2, 1, 3, -2, 0, 1, 23400, true);

        self::assertDuration('-P2M10DT-2H1.0234S', $duration);
    }

    #[DataProvider('dataFromNative')]
    public function testFromNative(string $expected, \DateInterval $interval): void
    {
        static::assertDuration($expected, Duration::fromNative($interval));
    }

    public static function dataFromNative(): iterable
    {
        yield ['P1DT20M', new \DateInterval('P1DT20M')];

        $interval = new \DateInterval('P1DT20M');
        $interval->invert = 1;
        yield ['-P1DT20M', $interval];

        yield ['P21D', new \DateInterval('P3W')];

        yield ['P-1Y1M', \DateInterval::createFromDateString('-1 year +1 month')];
    }

    public function testFromNativeClone(): void
    {
        $interval = new \DateInterval('P1D');
        $duration = Duration::fromNative($interval);

        $interval->d = 2;

        static::assertDuration('P1D', $duration);
    }

    #[DataProvider('dataCast')]
    public function testCast(string $expected, $duration): void
    {
        static::assertDuration($expected, Duration::cast($duration));
    }

    public static function dataCast(): iterable
    {
        return [
            ['P1DT20M', Duration::fromString('P1DT20M')],
            ['P2MT20M', new \DateInterval('P2MT20M')],
            ['P1YT20M', 'P1YT20M'],
        ];
    }

    #[DataProvider('dataYears')]
    public function testYears(string $expected, int $years): void
    {
        static::assertDuration($expected, Duration::years($years));
    }

    public static function dataYears(): iterable
    {
        return [
            ['P-2Y', -2],
            ['P1Y', 1],
            ['P3Y', 3],
        ];
    }

    #[DataProvider('dataMonths')]
    public function testMonths(string $expected, int $months): void
    {
        static::assertDuration($expected, Duration::months($months));
    }

    public static function dataMonths(): iterable
    {
        return [
            ['P-2M', -2],
            ['P1M', 1],
            ['P3M', 3],
        ];
    }

    #[DataProvider('dataWeeks')]
    public function testWeeks(string $expected, int $weeks): void
    {
        static::assertDuration($expected, Duration::weeks($weeks));
    }

    public static function dataWeeks(): iterable
    {
        return [
            ['P-14D', -2],
            ['P7D', 1],
            ['P21D', 3],
        ];
    }

    #[DataProvider('dataDays')]
    public function testDays(string $expected, int $days): void
    {
        static::assertDuration($expected, Duration::days($days));
    }

    public static function dataDays(): iterable
    {
        return [
            ['P-2D', -2],
            ['P1D', 1],
            ['P3D', 3],
        ];
    }

    #[DataProvider('dataHours')]
    public function testHours(string $expected, int $hours): void
    {
        static::assertDuration($expected, Duration::hours($hours));
    }

    public static function dataHours(): iterable
    {
        return [
            ['PT-2H', -2],
            ['PT1H', 1],
            ['PT3H', 3],
        ];
    }

    #[DataProvider('dataMinutes')]
    public function testMinutes(string $expected, int $minutes): void
    {
        static::assertDuration($expected, Duration::minutes($minutes));
    }

    public static function dataMinutes(): iterable
    {
        return [
            ['PT-2M', -2],
            ['PT1M', 1],
            ['PT3M', 3],
        ];
    }

    #[DataProvider('dataSeconds')]
    public function testSeconds(string $expected, int $seconds): void
    {
        static::assertDuration($expected, Duration::seconds($seconds));
    }

    public static function dataSeconds(): iterable
    {
        return [
            ['PT-2S', -2],
            ['PT1S', 1],
            ['PT3S', 3],
        ];
    }

    #[DataProvider('dataGetYears')]
    public function testGetYears(int $expected, string $duration): void
    {
        $duration = Duration::fromString($duration);

        self::assertSame($expected, $duration->getYears());
    }

    public static function dataGetYears(): iterable
    {
        return [
            [0, 'P6MT3M'],
            [1, 'P1Y6MT3M'],
            [-5, 'P-5YT1.234567S'],
            [3, '-P3Y'],
        ];
    }

    #[DataProvider('dataGetMonths')]
    public function testGetMonths(int $expected, string $duration): void
    {
        $duration = Duration::fromString($duration);

        self::assertSame($expected, $duration->getMonths());
    }

    public static function dataGetMonths(): iterable
    {
        return [
            [0, 'P6YT3M'],
            [1, 'P6Y1MT3M'],
            [-5, 'P-5MT1.234567S'],
            [3, '-P3M'],
        ];
    }

    #[DataProvider('dataGetDays')]
    public function testGetDays(int $expected, string $duration): void
    {
        $duration = Duration::fromString($duration);

        self::assertSame($expected, $duration->getDays());
    }

    public static function dataGetDays(): iterable
    {
        return [
            [0, 'P6YT3M'],
            [1, 'P6Y1DT3M'],
            [-5, 'P-5DT1.234567S'],
            [3, '-P3D'],
        ];
    }

    #[DataProvider('dataGetHours')]
    public function testGetHours(int $expected, string $duration): void
    {
        $duration = Duration::fromString($duration);

        self::assertSame($expected, $duration->getHours());
    }

    public static function dataGetHours(): iterable
    {
        return [
            [0, 'P2YT3M'],
            [1, 'P2YT1H3M'],
            [-5, 'PT-5H1.234567S'],
            [3, '-PT3H'],
        ];
    }

    #[DataProvider('dataGetMinutes')]
    public function testGetMinutes(int $expected, string $duration): void
    {
        $duration = Duration::fromString($duration);

        self::assertSame($expected, $duration->getMinutes());
    }

    public static function dataGetMinutes(): iterable
    {
        return [
            [0, 'P2YT3H'],
            [1, 'P2YT3H1M'],
            [-5, 'PT-5M1.234567S'],
            [3, '-PT3M'],
        ];
    }

    #[DataProvider('dataGetSeconds')]
    public function testGetSeconds(int $expected, string $duration): void
    {
        $duration = Duration::fromString($duration);

        self::assertSame($expected, $duration->getSeconds());
    }

    public static function dataGetSeconds(): iterable
    {
        return [
            [0, 'P2YT3H'],
            [0, 'P2YT3H0.123456S'],
            [1, 'P2YT3H1S'],
            [-5, 'PT-5M-5.234567S'],
            [3, '-PT3.1S'],
        ];
    }

    #[DataProvider('dataGetMicroseconds')]
    public function testGetMicroseconds(int $expected, string $duration): void
    {
        $duration = Duration::fromString($duration);

        self::assertSame($expected, $duration->getMicroseconds());
    }

    public static function dataGetMicroseconds(): iterable
    {
        return [
            [0, 'P2YT3H'],
            [0, 'P2YT3H1S'],
            [123, 'P2YT3H1.000123S'],
            [12300, 'P2YT3H1.0123S'],
            [-234567, 'PT-5M-5.234567S'],
            [100000, '-PT3.1S'],
        ];
    }

    #[DataProvider('dataGetTotalMonths')]
    public function testGetTotalMonths(int $expected, string $duration): void
    {
        $duration = Duration::fromString($duration);

        self::assertSame($expected, $duration->getTotalMonths());
    }

    public static function dataGetTotalMonths(): iterable
    {
        return [
            [0, 'P40DT3H'],
            [27, 'P2Y3M10D'],
            [12, 'P1Y'],
            [-11, 'P-1Y1M'],
            [34, 'P3Y-2M'],
            [3, '-P3M'],
        ];
    }

    #[DataProvider('dataGetTotalDays')]
    public function testGetTotalDays(?int $expected, Duration $duration): void
    {
        self::assertSame($expected, $duration->getTotalDays());
    }

    public static function dataGetTotalDays(): iterable
    {
        return [
            [null, Duration::fromString('P4D')],
            [35, Date::fromString('2020-03-19')->diff(Date::fromString('2020-04-23'))],
            [2, DateTime::fromString('2020-07-19 13:00')->diff(DateTime::fromString('2020-07-17 11:30'))],
        ];
    }

    #[DataProvider('dataIsInverted')]
    public function testIsInverted(bool $expected, Duration $duration): void
    {
        self::assertSame($expected, $duration->isInverted());
    }

    public static function dataIsInverted(): iterable
    {
        return [
            [false, Duration::fromString('P4D')],
            [false, Duration::fromString('P-2Y')],
            [true, Duration::fromString('-P1YT1H')],
            [false, Date::fromString('2020-03-19')->diff(Date::fromString('2020-04-23'))],
            [true, DateTime::fromString('2020-07-19 13:00')->diff(DateTime::fromString('2020-07-17 11:30'))],
        ];
    }

    #[DataProvider('dataIsZero')]
    public function testIsZero(bool $expected, Duration $duration): void
    {
        self::assertSame($expected, $duration->isZero());
    }

    public static function dataIsZero(): iterable
    {
        return [
            [false, Duration::fromString('P4D')],
            [false, Duration::fromString('PT-1H')],
            [false, Duration::fromString('-P-2YT1M')],
            [true, Duration::fromString('P0D')],
            [true, Duration::fromString('PT0.000S')],
            [false, Date::fromString('2020-03-19')->diff(Date::fromString('2020-04-23'))],
            [true, DateTime::fromString('2020-07-19 13:05:20.123456')->diff(DateTime::fromString('2020-07-19 15:35:20.123456', TimeZone::fromString('Asia/Tehran')))],
        ];
    }

    #[DataProvider('dataFormat')]
    public function testFormat(string $expected, string $duration, string $format): void
    {
        $duration = Duration::fromString($duration);

        self::assertSame($expected, $duration->format($format));
    }

    public static function dataFormat(): iterable
    {
        return [
            ['+ 2 months, 02h, 0min, 1sec, 123ms', 'P1Y2M3DT2H1.000123S', '%R %m months, %Hh, %imin, %ssec, %fms'],
            ['- 3 days', '-P3D', '%r %d days'],
            // ['-2.034500', 'PT-2.0345S', '%s.%F'],
        ];
    }

    public function testToNative(): void
    {
        $duration = Duration::fromString('P1Y5DT3H-15M0.0123S');
        $interval = $duration->toNative();

        self::assertSame(1, $interval->y);
        self::assertSame(0, $interval->m);
        self::assertSame(-15, $interval->i);
        self::assertSame(0.0123, $interval->f);

        $interval->y = 2;
        self::assertSame(1, $duration->getYears());
    }

    public function testSetState(): void
    {
        $string = 'P1YT3H-15M';
        $duration = null;
        eval('$duration = '.var_export(Duration::fromString($string), true).';');

        self::assertDuration($string, $duration);
    }

    public function testJsonSerialize(): void
    {
        $duration = Duration::fromString('P1DT2H');

        self::assertJsonStringEqualsJsonString('"P1DT2H"', json_encode($duration));
    }

    #[DataProvider('dataSerialization')]
    public function testSerialization(Duration $duration): void
    {
        $duration2 = unserialize(serialize($duration));

        static::assertDuration($duration->toIso(), $duration2);
        self::assertSame($duration->getTotalDays(), $duration2->getTotalDays());
    }

    public static function dataSerialization(): iterable
    {
        return [
            [Duration::fromString('P2YT3H')],
            [DateTime::fromString('2020-07-16 18:30:00.123456')->diff(DateTime::fromString('2020-07-10 12:21'))],
        ];
    }

    public function testDebugInfo(): void
    {
        $duration = Duration::fromString('P1Y10DT3H-15M-1.0123S');

        $expected = [
            'inverted' => false,
            'years' => 1,
            'months' => 0,
            'days' => 10,
            'hours' => 3,
            'minutes' => -15,
            'seconds' => -1,
            'microseconds' => -12300,
            'totalDays' => null,
        ];

        self::assertSame($expected, $duration->__debugInfo());
    }

    private static function assertDuration(string $expected, $duration): void
    {
        self::assertInstanceOf(Duration::class, $duration);
        self::assertSame($expected, (string) $duration);
    }
}
