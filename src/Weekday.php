<?php

declare(strict_types=1);

namespace HillValley\Fluxcap;

use HillValley\Fluxcap\Exception\MissingIntlExtensionException;
use function get_class;
use function gettype;
use function is_int;
use function is_object;

/**
 * @psalm-immutable
 */
final class Weekday implements \JsonSerializable
{
    use Base\EnumTrait;

    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;
    public const SUNDAY = 7;

    private const NAMES = [
        self::MONDAY => 'Monday',
        self::TUESDAY => 'Tuesday',
        self::WEDNESDAY => 'Wednesday',
        self::THURSDAY => 'Thursday',
        self::FRIDAY => 'Friday',
        self::SATURDAY => 'Saturday',
        self::SUNDAY => 'Sunday',
    ];

    private const ABBREVIATIONS = [
        self::MONDAY => 'Mon',
        self::TUESDAY => 'Tue',
        self::WEDNESDAY => 'Wed',
        self::THURSDAY => 'Thu',
        self::FRIDAY => 'Fri',
        self::SATURDAY => 'Sat',
        self::SUNDAY => 'Sun',
    ];

    /** @psalm-pure */
    public static function monday(): self
    {
        return self::get(self::MONDAY);
    }

    /** @psalm-pure */
    public static function tuesday(): self
    {
        return self::get(self::TUESDAY);
    }

    /** @psalm-pure */
    public static function wednesday(): self
    {
        return self::get(self::WEDNESDAY);
    }

    /** @psalm-pure */
    public static function thursday(): self
    {
        return self::get(self::THURSDAY);
    }

    /** @psalm-pure */
    public static function friday(): self
    {
        return self::get(self::FRIDAY);
    }

    /** @psalm-pure */
    public static function saturday(): self
    {
        return self::get(self::SATURDAY);
    }

    /** @psalm-pure */
    public static function sunday(): self
    {
        return self::get(self::SUNDAY);
    }

    /**
     * @param int|self|Date|DateTime|\DateTimeInterface $weekday
     * @psalm-pure
     */
    public static function cast($weekday): self
    {
        if ($weekday instanceof self) {
            return $weekday;
        }

        if ($weekday instanceof Date) {
            return $weekday->toWeekday();
        }

        if ($weekday instanceof DateTime) {
            return $weekday->toWeekday();
        }

        if ($weekday instanceof \DateTimeInterface) {
            /** @psalm-suppress ImpureMethodCall */
            return self::get((int) $weekday->format('N'));
        }

        if (is_int($weekday)) {
            return self::get($weekday);
        }

        // @codeCoverageIgnoreStart
        throw new \TypeError(sprintf(
            '%s(): Argument #1 must be of type %s, %s given',
            __METHOD__,
            implode('|', ['int', self::class, Date::class, DateTime::class, \DateTimeInterface::class]),
            is_object($weekday) ? get_class($weekday) : gettype($weekday),
        ));
        // @codeCoverageIgnoreEnd
    }

    public function getIntlName(): string
    {
        /** @psalm-suppress ImpureFunctionCall */
        if (!class_exists(\IntlDateFormatter::class)) {
            throw MissingIntlExtensionException::fromMethod(__METHOD__); // @codeCoverageIgnore
        }

        return $this->formatIntl('cccc');
    }

    public function getIntlAbbreviation(): string
    {
        /** @psalm-suppress ImpureFunctionCall */
        if (!class_exists(\IntlDateFormatter::class)) {
            throw MissingIntlExtensionException::fromMethod(__METHOD__); // @codeCoverageIgnore
        }

        return $this->formatIntl('ccc');
    }
}
