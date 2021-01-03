<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Base;

use HillValley\Fluxcap\TimeZone;

/**
 * @internal
 */
final class IntlFormatter
{
    /** @var array<string, array<string, array<string, \IntlDateFormatter>>> */
    private static array $formatters = [];

    /**
     * @psalm-mutation-free
     */
    public static function formatTimestamp(int $timestamp, string $pattern): string
    {
        $formatter = self::getFormatter(TimeZone::default()->toNative(), \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $pattern);

        /** @psalm-suppress ImpureMethodCall */
        return $formatter->format($timestamp);
    }

    /**
     * @param \IntlDateFormatter::FULL|\IntlDateFormatter::LONG|\IntlDateFormatter::MEDIUM|\IntlDateFormatter::SHORT|\IntlDateFormatter::NONE $dateFormat
     * @param \IntlDateFormatter::FULL|\IntlDateFormatter::LONG|\IntlDateFormatter::MEDIUM|\IntlDateFormatter::SHORT|\IntlDateFormatter::NONE $timeFormat
     * @psalm-mutation-free
     */
    public static function formatDateTime(\DateTimeImmutable $dateTime, int $dateFormat, int $timeFormat, ?string $pattern = null): string
    {
        $formatter = self::getFormatter($dateTime->getTimezone(), $dateFormat, $timeFormat, $pattern);

        /** @psalm-suppress ImpureMethodCall */
        return $formatter->format($dateTime);
    }

    /**
     * @param \IntlDateFormatter::FULL|\IntlDateFormatter::LONG|\IntlDateFormatter::MEDIUM|\IntlDateFormatter::SHORT|\IntlDateFormatter::NONE $dateFormat
     * @param \IntlDateFormatter::FULL|\IntlDateFormatter::LONG|\IntlDateFormatter::MEDIUM|\IntlDateFormatter::SHORT|\IntlDateFormatter::NONE $timeFormat
     * @psalm-mutation-free
     */
    private static function getFormatter(\DateTimeZone $timeZone, int $dateFormat, int $timeFormat, ?string $pattern): \IntlDateFormatter
    {
        $locale = \Locale::getDefault();
        $timeZone = $timeZone->getName();
        $cacheKey = $pattern ?? $dateFormat.'-'.$timeFormat;

        /** @psalm-suppress ImpureStaticProperty */
        $formatter = self::$formatters[$locale][$timeZone][$cacheKey] ?? null;

        if ($formatter) {
            return $formatter;
        }

        if (null === $pattern) {
            $pattern = '';
        } else {
            $dateFormat = \IntlDateFormatter::NONE;
            $timeFormat = \IntlDateFormatter::NONE;
        }

        $formatter = new \IntlDateFormatter($locale, $dateFormat, $timeFormat, \IntlTimeZone::createTimeZone($timeZone), null, $pattern);

        switch ($dateFormat) {
            case \IntlDateFormatter::SHORT:
                // Avoid two-digit year format, which is used for some languages in short date format
                /** @psalm-suppress ImpureMethodCall */
                $formatter->setPattern(str_replace(['yyyy', 'yy'], 'y', $formatter->getPattern()));

                break;
            case \IntlDateFormatter::MEDIUM:
                // Change german medium date format to "2. Sep. 2020", which is more similar to the medium format of other languages
                if ('d' === $locale[0] && 'e' === $locale[1]) {
                    /** @psalm-suppress ImpureMethodCall */
                    $formatter->setPattern(str_replace('dd.MM.y', 'd. LLL. y', $formatter->getPattern()));
                }

                break;
        }

        /** @psalm-suppress ImpureStaticProperty */
        return self::$formatters[$locale][$timeZone][$cacheKey] = $formatter;
    }
}
