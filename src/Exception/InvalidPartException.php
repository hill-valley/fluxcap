<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Exception;

final class InvalidPartException extends \DomainException implements Exception
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    /** @internal */
    public static function create(string $message): self
    {
        return new self($message);
    }

    /** @internal */
    public static function fromMonth(int $month): self
    {
        return new self("Month part must be between 1 and 12, but $month given.");
    }

    /** @internal */
    public static function fromDay(int $day): self
    {
        return new self("Day part must be between 1 and 31, but $day given.");
    }

    /** @internal */
    public static function fromDate(int $year, int $month, int $day): self
    {
        $to = idate('t', strtotime("$year-$month-01"));

        return new self("Day part for month $month of year $year must be between 1 and $to, but $day given.");
    }

    /** @internal */
    public static function fromHour(int $hour): self
    {
        return new self("Hour part must be between 0 and 23, but $hour given.");
    }

    /** @internal */
    public static function fromMinute(int $minute): self
    {
        return new self("Minute part must be between 0 and 59, but $minute given.");
    }

    /** @internal */
    public static function fromSecond(int $second): self
    {
        return new self("Seconds part must be between 0 and 59, but $second given.");
    }

    /** @internal */
    public static function fromMicrosecond(int $microsecond): self
    {
        return new self("Microseconds part must be between 0 and 999999, but $microsecond given.");
    }
}
