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
final class Time implements \JsonSerializable
{
    use Base\CompareTrait;
    use Base\FormatTrait;
    use Base\ModifyTrait;
    use Base\TimeTrait;

    private const DATE = '1970-01-01';

    private \DateTimeImmutable $dateTime;

    private function __construct(\DateTimeImmutable $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /** @psalm-mutation-free */
    public static function now(): self
    {
        return self::fromNative(new \DateTimeImmutable('now'));
    }

    /** @psalm-mutation-free */
    public static function fromString(string $time): self
    {
        if ('' === $time) {
            throw InvalidStringException::create('The time string can not be empty (use "now" for current time).');
        }

        if ('@' === $time[0] && preg_match('/^@(\d+)$/', $time, $match)) {
            return self::fromTimestamp((int) $match[1]);
        }

        try {
            $native = new \DateTimeImmutable($time);
        } catch (\Exception $exception) {
            throw InvalidStringException::wrap($exception);
        }

        return self::fromNative($native);
    }

    /** @psalm-mutation-free */
    public static function fromFormat(string $format, string $time): self
    {
        $native = \DateTimeImmutable::createFromFormat($format, $time, TimeZone::utc()->toNative());

        if (false === $native) {
            throw FormatMismatchException::create($format, $time);
        }

        return self::fromNative($native);
    }

    /** @psalm-pure */
    public static function fromParts(int $hour, int $minute = 0, int $second = 0, int $microsecond = 0): self
    {
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

        $time = "$hour:$minute:$second.".sprintf('%06d', $microsecond);

        return new self(new \DateTimeImmutable(self::DATE.' '.$time, TimeZone::utc()->toNative()));
    }

    /** @psalm-mutation-free */
    public static function fromTimestamp(int $timestamp): self
    {
        return self::fromString(date('H:i:s', $timestamp));
    }

    /** @psalm-pure */
    public static function fromNative(\DateTimeInterface $dateTime): self
    {
        /** @psalm-suppress ImpureMethodCall */
        $dateTime = new \DateTimeImmutable(self::DATE.' '.$dateTime->format('H:i:s.u'), TimeZone::utc()->toNative());

        return new self($dateTime);
    }

    /**
     * @param int|string|self|DateTime|\DateTimeInterface $dateTime
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

        if ($dateTime instanceof DateTime) {
            return $dateTime->toTime();
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
            implode('|', ['int', 'string', self::class, DateTime::class, \DateTimeInterface::class]),
            is_object($dateTime) ? get_class($dateTime) : gettype($dateTime),
        ));
        // @codeCoverageIgnoreEnd
    }

    public function toIso(): string
    {
        return $this->dateTime->format('H:i:s.u');
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

        return IntlFormatter::formatDateTime($this->dateTime, \IntlDateFormatter::NONE, $format ?? \IntlDateFormatter::MEDIUM, $pattern);
    }

    public function toDateTime(): DateTime
    {
        return DateTime::fromString($this->format('Y-m-d H:i:s.u'));
    }

    public function toNative(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->format('Y-m-d H:i:s.u'));
    }

    public function toMutable(): \DateTime
    {
        return new \DateTime($this->format('Y-m-d H:i:s.u'));
    }

    public function toTimestamp(): int
    {
        return strtotime($this->format('Y-m-d H:i:s'));
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
        return ['time' => $this->format('H:i:s.u')];
    }

    /**
     * @param array{time: string} $data
     */
    public function __unserialize(array $data): void
    {
        $this->dateTime = new \DateTimeImmutable(self::DATE.' '.$data['time'], TimeZone::utc()->toNative());
    }

    public function __debugInfo(): array
    {
        return [
            'time' => $this->toIso(),
        ];
    }
}
