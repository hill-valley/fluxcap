<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Tests\Base;

use HillValley\Fluxcap\Base\IntlFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \HillValley\Fluxcap\Base\IntlFormatter
 */
final class IntlFormatterTest extends TestCase
{
    protected function tearDown(): void
    {
        \Locale::setDefault('de-DE');
    }

    /** @dataProvider dataFormatDateTime */
    public function testFormatDateTime(string $expected, string $dateTime, string $locale, int $dateFormat, int $timeFormat = \IntlDateFormatter::NONE): void
    {
        \Locale::setDefault($locale);
        $dateTime = new \DateTimeImmutable($dateTime);

        self::assertSame($expected, IntlFormatter::formatDateTime($dateTime, $dateFormat, $timeFormat));
    }

    public function dataFormatDateTime(): iterable
    {
        return [
            ['03/09/2020', '2020-09-03', 'en-GB', \IntlDateFormatter::SHORT],
            ['03.09.2020', '2020-09-03', 'de-DE', \IntlDateFormatter::SHORT],
            ['Sep 3, 2020, 12:00 AM', '2020-09-03', 'en-US', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT],
            ['3. Sep. 2020, 00:00', '2020-09-03', 'de-DE', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT],
        ];
    }
}
