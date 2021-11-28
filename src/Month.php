<?php

declare(strict_types=1);

namespace HillValley\Fluxcap;

use HillValley\Fluxcap\Exception\MissingIntlExtensionException;

/**
 * @psalm-immutable
 */
final class Month implements \JsonSerializable, \Stringable
{
    use Base\EnumTrait;

    public const JANUARY = 1;
    public const FEBRUARY = 2;
    public const MARCH = 3;
    public const APRIL = 4;
    public const MAY = 5;
    public const JUNE = 6;
    public const JULY = 7;
    public const AUGUST = 8;
    public const SEPTEMBER = 9;
    public const OCTOBER = 10;
    public const NOVEMBER = 11;
    public const DECEMBER = 12;

    private const NAMES = [
        self::JANUARY => 'January',
        self::FEBRUARY => 'February',
        self::MARCH => 'March',
        self::APRIL => 'April',
        self::MAY => 'May',
        self::JUNE => 'June',
        self::JULY => 'July',
        self::AUGUST => 'August',
        self::SEPTEMBER => 'September',
        self::OCTOBER => 'October',
        self::NOVEMBER => 'November',
        self::DECEMBER => 'December',
    ];

    private const ABBREVIATIONS = [
        self::JANUARY => 'Jan',
        self::FEBRUARY => 'Feb',
        self::MARCH => 'Mar',
        self::APRIL => 'Apr',
        self::MAY => 'May',
        self::JUNE => 'Jun',
        self::JULY => 'Jul',
        self::AUGUST => 'Aug',
        self::SEPTEMBER => 'Sep',
        self::OCTOBER => 'Oct',
        self::NOVEMBER => 'Nov',
        self::DECEMBER => 'Dec',
    ];

    /** @psalm-pure */
    public static function january(): self
    {
        return self::get(self::JANUARY);
    }

    /** @psalm-pure */
    public static function february(): self
    {
        return self::get(self::FEBRUARY);
    }

    /** @psalm-pure */
    public static function march(): self
    {
        return self::get(self::MARCH);
    }

    /** @psalm-pure */
    public static function april(): self
    {
        return self::get(self::APRIL);
    }

    /** @psalm-pure */
    public static function may(): self
    {
        return self::get(self::MAY);
    }

    /** @psalm-pure */
    public static function june(): self
    {
        return self::get(self::JUNE);
    }

    /** @psalm-pure */
    public static function july(): self
    {
        return self::get(self::JULY);
    }

    /** @psalm-pure */
    public static function august(): self
    {
        return self::get(self::AUGUST);
    }

    /** @psalm-pure */
    public static function september(): self
    {
        return self::get(self::SEPTEMBER);
    }

    /** @psalm-pure */
    public static function october(): self
    {
        return self::get(self::OCTOBER);
    }

    /** @psalm-pure */
    public static function november(): self
    {
        return self::get(self::NOVEMBER);
    }

    /** @psalm-pure */
    public static function december(): self
    {
        return self::get(self::DECEMBER);
    }

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
            return self::get((int) $month->format('n'));
        }

        return self::get($month);
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
