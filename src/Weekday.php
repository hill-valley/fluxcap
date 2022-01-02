<?php

declare(strict_types=1);

namespace HillValley\Fluxcap;

use HillValley\Fluxcap\Exception\MissingIntlExtensionException;

/**
 * @psalm-immutable
 */
enum Weekday: int
{
    use Base\EnumTrait;

    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    /** @psalm-pure */
    public static function cast(int|self|Date|DateTime|\DateTimeInterface $weekday): self
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
            return self::from((int) $weekday->format('N'));
        }

        return self::from($weekday);
    }

    public function getAbbreviation(): string
    {
        return match ($this) {
            self::Monday => 'Mon',
            self::Tuesday => 'Tue',
            self::Wednesday => 'Wed',
            self::Thursday => 'Thu',
            self::Friday => 'Fri',
            self::Saturday => 'Sat',
            self::Sunday => 'Sun',
        };
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
