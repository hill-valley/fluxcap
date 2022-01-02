<?php

declare(strict_types=1);

namespace HillValley\Fluxcap;

use HillValley\Fluxcap\Exception\MissingIntlExtensionException;

/**
 * @psalm-immutable
 */
enum Month: int
{
    use Base\EnumTrait;

    case January = 1;
    case February = 2;
    case March = 3;
    case April = 4;
    case May = 5;
    case June = 6;
    case July = 7;
    case August = 8;
    case September = 9;
    case October = 10;
    case November = 11;
    case December = 12;

    /** @psalm-pure */
    public static function cast(int|self|Date|DateTime|\DateTimeInterface $month): self
    {
        if ($month instanceof self) {
            return $month;
        }

        if ($month instanceof Date) {
            return $month->toMonth();
        }

        if ($month instanceof DateTime) {
            return $month->toMonth();
        }

        if ($month instanceof \DateTimeInterface) {
            /** @psalm-suppress ImpureMethodCall */
            return self::from((int) $month->format('n'));
        }

        return self::from($month);
    }

    public function getAbbreviation(): string
    {
        return match ($this) {
            self::January => 'Jan',
            self::February => 'Feb',
            self::March => 'Mar',
            self::April => 'Apr',
            self::May => 'May',
            self::June => 'Jun',
            self::July => 'Jul',
            self::August => 'Aug',
            self::September => 'Sep',
            self::October => 'Oct',
            self::November => 'Nov',
            self::December => 'Dec',
        };
    }

    public function getIntlName(): string
    {
        /** @psalm-suppress ImpureFunctionCall */
        if (!class_exists(\IntlDateFormatter::class)) {
            throw MissingIntlExtensionException::fromMethod(__METHOD__); // @codeCoverageIgnore
        }

        return $this->formatIntl('LLLL');
    }

    public function getIntlAbbreviation(): string
    {
        /** @psalm-suppress ImpureFunctionCall */
        if (!class_exists(\IntlDateFormatter::class)) {
            throw MissingIntlExtensionException::fromMethod(__METHOD__); // @codeCoverageIgnore
        }

        return $this->formatIntl('LLL');
    }
}
