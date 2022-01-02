<?php

declare(strict_types=1);

namespace HillValley\Fluxcap;

use HillValley\Fluxcap\Base\IntlFormatter;
use HillValley\Fluxcap\Exception\FormatMismatchException;
use HillValley\Fluxcap\Exception\InvalidPartException;
use HillValley\Fluxcap\Exception\InvalidStringException;
use HillValley\Fluxcap\Exception\MissingIntlExtensionException;
use function is_int;

/**
 * @psalm-immutable
 */
final class Date implements \JsonSerializable, \Stringable
{
    use Base\CompareTrait;
    use Base\DateTrait;
    use Base\FormatTrait;
    use Base\ModifyTrait;

    private readonly \DateTimeImmutable $dateTime;

    private function __construct(\DateTimeImmutable $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /** @psalm-mutation-free */
    public static function today(?TimeZone $timeZone = null): self
    {
        return self::fromKeyword('today', $timeZone);
    }

    /** @psalm-mutation-free */
    public static function yesterday(?TimeZone $timeZone = null): self
    {
        return self::fromKeyword('yesterday', $timeZone);
    }

    /** @psalm-mutation-free */
    public static function tomorrow(?TimeZone $timeZone = null): self
    {
        return self::fromKeyword('tomorrow', $timeZone);
    }

    /** @psalm-mutation-free */
    public static function fromString(string $date): self
    {
        if ('' === $date) {
            throw InvalidStringException::create('The date string can not be empty (use "today" for current date).');
        }

        $timeZone = TimeZone::default()->toNative();

        try {
            $date = new \DateTimeImmutable($date, $timeZone);
        } catch (\Exception $exception) {
            throw InvalidStringException::wrap($exception);
        }

        if ($timeZone->getName() !== $date->getTimezone()->getName()) {
            $date = $date->setTimezone($timeZone);
        }
        if ('UTC' !== $timeZone->getName()) {
            $date = new \DateTimeImmutable($date->format('Y-m-d'), TimeZone::utc()->toNative());
        }

        return new self($date);
    }

    /** @psalm-mutation-free */
    public static function fromFormat(string $format, string $date): self
    {
        $native = \DateTimeImmutable::createFromFormat($format, $date, TimeZone::utc()->toNative());

        if (false === $native) {
            throw FormatMismatchException::create($format, $date);
        }

        return self::fromNative($native);
    }

    /** @psalm-mutation-free */
    public static function fromParts(int $year, int $month, int $day): self
    {
        if ($month < 1 || $month > 12) {
            throw InvalidPartException::fromMonth($month);
        }
        if ($day < 1 || $day > 31) {
            throw InvalidPartException::fromDay($day);
        }

        $date = self::fromString("$year-$month-$day");

        if ($date->getDay() !== $day) {
            throw InvalidPartException::fromDate($year, $month, $day);
        }

        return $date;
    }

    /** @psalm-mutation-free */
    public static function fromTimestamp(int $timestamp): self
    {
        return self::fromString('@'.$timestamp);
    }

    /** @psalm-pure */
    public static function fromNative(\DateTimeInterface $dateTime): self
    {
        if (\DateTimeImmutable::class !== $dateTime::class || '00:00:00.000000 UTC' !== $dateTime->format('H:i:s.u e')) {
            /** @psalm-suppress ImpureMethodCall */
            $dateTime = new \DateTimeImmutable($dateTime->format('Y-m-d'), TimeZone::utc()->toNative());
        }

        return new self($dateTime);
    }

    /** @psalm-mutation-free */
    public static function cast(int|string|self|DateTime|\DateTimeInterface $dateTime): self
    {
        if (is_int($dateTime)) {
            return self::fromTimestamp($dateTime);
        }

        if ($dateTime instanceof self) {
            return $dateTime;
        }

        if ($dateTime instanceof DateTime) {
            return $dateTime->toDate();
        }

        if ($dateTime instanceof \DateTimeInterface) {
            return self::fromNative($dateTime);
        }

        return self::fromString($dateTime);
    }

    public function toIso(): string
    {
        return $this->dateTime->format('Y-m-d');
    }

    /**
     * @param \IntlDateFormatter::FULL|\IntlDateFormatter::LONG|\IntlDateFormatter::MEDIUM|\IntlDateFormatter::SHORT|\IntlDateFormatter::NONE|null $format
     */
    public function formatIntl(?int $format = null, ?string $pattern = null): string
    {
        /** @psalm-suppress ImpureFunctionCall */
        if (!class_exists(\IntlDateFormatter::class)) {
            throw MissingIntlExtensionException::fromMethod(__METHOD__); // @codeCoverageIgnore
        }

        return IntlFormatter::formatDateTime($this->dateTime, $format ?? \IntlDateFormatter::LONG, \IntlDateFormatter::NONE, $pattern);
    }

    public function isPast(): bool
    {
        return (int) $this->dateTime->format('Ymd') < (int) date('Ymd');
    }

    public function isFuture(): bool
    {
        return (int) $this->dateTime->format('Ymd') > (int) date('Ymd');
    }

    public function isToday(): bool
    {
        return $this->dateTime->format('Ymd') === date('Ymd');
    }

    public function toDateTime(?TimeZone $timeZone = null): DateTime
    {
        return DateTime::fromString($this->toIso(), $timeZone);
    }

    public function toNative(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->toIso());
    }

    public function toMutable(): \DateTime
    {
        return new \DateTime($this->toIso());
    }

    public function toTimestamp(): int
    {
        return strtotime($this->toIso());
    }

    /**
     * @param array{dateTime: \DateTimeImmutable} $data
     */
    public static function __set_state(array $data): self
    {
        return new self($data['dateTime']);
    }

    public function __serialize(): array
    {
        return ['date' => $this->toIso()];
    }

    /**
     * @param array{date: string} $data
     */
    public function __unserialize(array $data): void
    {
        $this->dateTime = new \DateTimeImmutable($data['date'], TimeZone::utc()->toNative());
    }

    public function __debugInfo(): array
    {
        return [
            'date' => $this->toIso(),
        ];
    }

    /** @psalm-mutation-free */
    private static function fromKeyword(string $keyword, ?TimeZone $timeZone): self
    {
        $timeZone ??= TimeZone::default();
        $date = new \DateTimeImmutable($keyword, $timeZone->toNative());

        if (!$timeZone->isUtc()) {
            $date = new \DateTimeImmutable($date->format('Y-m-d'), TimeZone::utc()->toNative());
        }

        return new self($date);
    }
}
