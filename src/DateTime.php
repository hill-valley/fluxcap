<?php

declare(strict_types=1);

namespace HillValley\Fluxcap;

use HillValley\Fluxcap\Base\IntlFormatter;
use HillValley\Fluxcap\Exception\FormatMismatchException;
use HillValley\Fluxcap\Exception\InvalidPartException;
use HillValley\Fluxcap\Exception\InvalidStringException;
use HillValley\Fluxcap\Exception\MissingIntlExtensionException;
use function get_class;
use function gettype;
use function is_int;
use function is_object;
use function is_string;

/**
 * @psalm-immutable
 */
final class DateTime implements \JsonSerializable
{
    use Base\CompareTrait;
    use Base\DateTrait;
    use Base\FormatTrait;
    use Base\ModifyTrait;
    use Base\TimeTrait;

    private \DateTimeImmutable $dateTime;
    private TimeZone $timeZone;

    private function __construct(\DateTimeImmutable $dateTime, ?TimeZone $timeZone = null)
    {
        $this->dateTime = $dateTime;
        $this->timeZone = $timeZone ?? TimeZone::fromNative($dateTime->getTimezone());
    }

    /** @psalm-mutation-free */
    public static function now(?TimeZone $timeZone = null): self
    {
        return self::fromString('now', $timeZone);
    }

    /** @psalm-mutation-free */
    public static function utcNow(): self
    {
        return self::fromString('now', TimeZone::utc());
    }

    /** @psalm-mutation-free */
    public static function today(?TimeZone $timeZone = null): self
    {
        return self::fromString('today', $timeZone);
    }

    /** @psalm-mutation-free */
    public static function yesterday(?TimeZone $timeZone = null): self
    {
        return self::fromString('yesterday', $timeZone);
    }

    /** @psalm-mutation-free */
    public static function tomorrow(?TimeZone $timeZone = null): self
    {
        return self::fromString('tomorrow', $timeZone);
    }

    /** @psalm-mutation-free */
    public static function fromString(string $dateTime, ?TimeZone $timeZone = null): self
    {
        if ('' === $dateTime) {
            throw InvalidStringException::create('The date-time string can not be empty (use "now" for current time).');
        }

        try {
            $dateTime = new \DateTimeImmutable($dateTime, $timeZone ? $timeZone->toNative() : null);
        } catch (\Exception $exception) {
            throw InvalidStringException::wrap($exception);
        }

        if ($timeZone && $dateTime->getTimezone()->getName() !== $timeZone->getName()) {
            $dateTime = $dateTime->setTimezone($timeZone->toNative());
        }

        return new self($dateTime, $timeZone);
    }

    /** @psalm-mutation-free */
    public static function utcFromString(string $dateTime): self
    {
        return self::fromString($dateTime, TimeZone::utc());
    }

    /** @psalm-mutation-free */
    public static function fromUtcString(string $dateTime, ?TimeZone $timeZone = null): self
    {
        return self::utcFromString($dateTime)->toTimeZone($timeZone ?? TimeZone::default());
    }

    /** @psalm-mutation-free */
    public static function fromFormat(string $format, string $dateTime, ?TimeZone $timeZone = null): self
    {
        $native = \DateTimeImmutable::createFromFormat($format, $dateTime, $timeZone ? $timeZone->toNative() : null);

        if (false === $native) {
            throw FormatMismatchException::create($format, $dateTime);
        }

        if ($timeZone && $native->getTimezone()->getName() !== $timeZone->getName()) {
            $native = $native->setTimezone($timeZone->toNative());
        }

        return new self($native, $timeZone);
    }

    /** @psalm-mutation-free */
    public static function fromParts(int $year, int $month, int $day, int $hour = 0, int $minute = 0, int $second = 0, int $microsecond = 0, ?TimeZone $timeZone = null): self
    {
        if ($month < 1 || $month > 12) {
            throw InvalidPartException::fromMonth($month);
        }
        if ($day < 1 || $day > 31) {
            throw InvalidPartException::fromDay($day);
        }

        if ($hour < 0 || $hour > 23) {
            throw InvalidPartException::fromHour($hour);
        }
        if ($minute < 0 || $minute > 59) {
            throw InvalidPartException::fromMinute($minute);
        }
        if ($second < 0 || $second > 59) {
            throw InvalidPartException::fromSecond($second);
        }
        if ($microsecond < 0 || $microsecond > 999999) {
            throw InvalidPartException::fromMicrosecond($microsecond);
        }

        $dateTime = self::fromString("$year-$month-$day $hour:$minute:$second.".sprintf('%06d', $microsecond), $timeZone);

        if ($dateTime->getDay() !== $day) {
            throw InvalidPartException::fromDate($year, $month, $day);
        }

        return $dateTime;
    }

    /** @psalm-mutation-free */
    public static function fromTimestamp(int $timestamp, ?TimeZone $timeZone = null): self
    {
        return self::fromString('@'.$timestamp)->toTimeZone($timeZone ?? TimeZone::default());
    }

    /** @psalm-pure */
    public static function fromNative(\DateTimeInterface $dateTime): self
    {
        if (\DateTimeImmutable::class !== get_class($dateTime)) {
            /** @psalm-suppress ImpureMethodCall */
            $dateTime = new \DateTimeImmutable($dateTime->format('Y-m-d H:i:s.u'), $dateTime->getTimezone());
        }

        return new self($dateTime);
    }

    /** @psalm-mutation-free */
    public static function combine(Date $date, Time $time, ?TimeZone $timeZone = null): self
    {
        return self::fromString($date->format('Y-m-d').'T'.$time->format('H:i:s.u'), $timeZone);
    }

    /**
     * @param int|string|self|Date|Time|\DateTimeInterface $dateTime
     * @psalm-mutation-free
     */
    public static function cast($dateTime): self
    {
        if (is_int($dateTime)) {
            return self::fromTimestamp($dateTime);
        }

        if ($dateTime instanceof self) {
            return $dateTime;
        }

        if ($dateTime instanceof Date) {
            return $dateTime->toDateTime();
        }

        if ($dateTime instanceof Time) {
            return $dateTime->toDateTime();
        }

        if ($dateTime instanceof \DateTimeInterface) {
            return self::fromNative($dateTime);
        }

        if (is_string($dateTime)) {
            return self::fromString($dateTime);
        }

        // @codeCoverageIgnoreStart
        throw new \TypeError(sprintf(
            '%s(): Argument #1 must be of type %s, %s given',
            __METHOD__,
            implode('|', ['int', 'string', self::class, Date::class, Time::class, \DateTimeInterface::class]),
            is_object($dateTime) ? get_class($dateTime) : gettype($dateTime),
        ));
        // @codeCoverageIgnoreEnd
    }

    public function getTimeZone(): TimeZone
    {
        return $this->timeZone;
    }

    public function getOffset(): int
    {
        return $this->dateTime->getOffset();
    }

    public function toIso(): string
    {
        if ($this->timeZone->isUtc()) {
            return $this->dateTime->format('Y-m-d\TH:i:s.u').'Z';
        }

        return $this->dateTime->format('Y-m-d\TH:i:s.uP');
    }

    public function formatLocalized(string $format): string
    {
        return strftime($format, $this->toTimestamp());
    }

    /**
     * @param \IntlDateFormatter::FULL|\IntlDateFormatter::LONG|\IntlDateFormatter::MEDIUM|\IntlDateFormatter::SHORT|\IntlDateFormatter::NONE|null $format
     * @param \IntlDateFormatter::FULL|\IntlDateFormatter::LONG|\IntlDateFormatter::MEDIUM|\IntlDateFormatter::SHORT|\IntlDateFormatter::NONE|null $timeFormat
     */
    public function formatIntl(?int $format = null, ?int $timeFormat = null, ?string $pattern = null): string
    {
        /** @psalm-suppress ImpureFunctionCall */
        if (!class_exists(\IntlDateFormatter::class)) {
            throw MissingIntlExtensionException::fromMethod(__METHOD__); // @codeCoverageIgnore
        }

        if (null === $format) {
            $format = \IntlDateFormatter::LONG;
            $timeFormat = \IntlDateFormatter::MEDIUM;
        }

        return IntlFormatter::formatDateTime($this->dateTime, $format, $timeFormat ?? $format, $pattern);
    }

    /**
     * @param \IntlDateFormatter::FULL|\IntlDateFormatter::LONG|\IntlDateFormatter::MEDIUM|\IntlDateFormatter::SHORT|\IntlDateFormatter::NONE|null $format
     */
    public function formatIntlDate(?int $format = null): string
    {
        /** @psalm-suppress ImpureFunctionCall */
        if (!class_exists(\IntlDateFormatter::class)) {
            throw MissingIntlExtensionException::fromMethod(__METHOD__); // @codeCoverageIgnore
        }

        return IntlFormatter::formatDateTime($this->dateTime, $format ?? \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
    }

    /**
     * @param \IntlDateFormatter::FULL|\IntlDateFormatter::LONG|\IntlDateFormatter::MEDIUM|\IntlDateFormatter::SHORT|\IntlDateFormatter::NONE|null $format
     */
    public function formatIntlTime(?int $format = null): string
    {
        /** @psalm-suppress ImpureFunctionCall */
        if (!class_exists(\IntlDateFormatter::class)) {
            throw MissingIntlExtensionException::fromMethod(__METHOD__); // @codeCoverageIgnore
        }

        return IntlFormatter::formatDateTime($this->dateTime, \IntlDateFormatter::NONE, $format ?? \IntlDateFormatter::MEDIUM);
    }

    public function isPast(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $this->dateTime->getTimestamp() < time();
    }

    public function isFuture(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $this->dateTime->getTimestamp() > time();
    }

    public function isToday(): bool
    {
        return $this->dateTime->setTimezone(TimeZone::default()->toNative())->format('Ymd') === date('Ymd');
    }

    public function toTimeZone(TimeZone $timeZone): self
    {
        if ($timeZone->equals($this->timeZone)) {
            return $this;
        }

        return new self($this->dateTime->setTimezone($timeZone->toNative()), $timeZone);
    }

    public function toDefaultTimeZone(): self
    {
        return $this->toTimeZone(TimeZone::default());
    }

    public function toUtc(): self
    {
        return $this->toTimeZone(TimeZone::utc());
    }

    public function toMidnight(): self
    {
        return new self($this->dateTime->setTime(0, 0, 0, 0), $this->timeZone);
    }

    public function toDate(): Date
    {
        return Date::fromNative($this->dateTime);
    }

    public function toTime(): Time
    {
        return Time::fromNative($this->dateTime);
    }

    public function toNative(): \DateTimeImmutable
    {
        return $this->dateTime;
    }

    public function toMutable(): \DateTime
    {
        return new \DateTime($this->format('Y-m-d H:i:s.u'), $this->timeZone->toNative());
    }

    public function toTimestamp(): int
    {
        return $this->dateTime->getTimestamp();
    }

    /**
     * @param array{dateTime: \DateTimeImmutable, timeZone: TimeZone} $data
     */
    public static function __set_state(array $data): self
    {
        return new self($data['dateTime'], $data['timeZone']);
    }

    public function __serialize(): array
    {
        return [
            'dateTime' => $this->format('Y-m-d\TH:i:s.u'),
            'timeZone' => $this->timeZone->getName(),
        ];
    }

    /**
     * @param array{dateTime: string, timeZone: string} $data
     */
    public function __unserialize(array $data): void
    {
        $this->timeZone = TimeZone::fromString($data['timeZone']);
        $this->dateTime = new \DateTimeImmutable($data['dateTime'], $this->timeZone->toNative());
    }

    public function __debugInfo(): array
    {
        return [
            'dateTime' => $this->toIso(),
            'timeZone' => $this->timeZone,
        ];
    }
}
