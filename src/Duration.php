<?php

declare(strict_types=1);

namespace HillValley\Fluxcap;

use HillValley\Fluxcap\Exception\InvalidStringException;
use function is_int;

/**
 * @psalm-immutable
 */
final class Duration implements \JsonSerializable, \Stringable
{
    private \DateInterval $interval;

    private function __construct(\DateInterval $interval)
    {
        $this->interval = $interval;
    }

    /** @psalm-pure */
    public static function fromString(string $duration): self
    {
        if (!preg_match('/^
            (?<inverted>-)?P
            (?:(?<years>-?\d+)Y)?
            (?:(?<months>-?\d+)M)?
            (?:(?<weeks>-?\d+)W)?
            (?:(?<days>-?\d+)D)?
            (?:T
                (?:(?<hours>-?\d+)H)?
                (?:(?<minutes>-?\d+)M)?
                (?:(?<seconds>-?\d+)(?:\.(?<microseconds>\d{1,6}))?S)?
            )?
        $/x', $duration, $parts)) {
            throw InvalidStringException::create("The string \"$duration\" is not a valid duration string.");
        }

        $seconds = $microseconds = 0;
        if (isset($parts['seconds'])) {
            $seconds = (int) $parts['seconds'];
            if (isset($parts['microseconds'])) {
                $microseconds = ($seconds < 0 ? -1 : 1) * (int) str_pad($parts['microseconds'], 6, '0');
            }
        }

        return self::fromParts(
            (int) ($parts['years'] ?? 0),
            (int) ($parts['months'] ?? 0),
            (int) ($parts['weeks'] ?? 0),
            (int) ($parts['days'] ?? 0),
            (int) ($parts['hours'] ?? 0),
            (int) ($parts['minutes'] ?? 0),
            $seconds,
            $microseconds,
            (bool) ($parts['inverted'] ?? false),
        );
    }

    /**
     * @psalm-pure
     * @psalm-suppress ImpurePropertyAssignment
     */
    public static function fromParts(int $years = 0, int $months = 0, int $weeks = 0, int $days = 0, int $hours = 0, int $minutes = 0, int $seconds = 0, int $microseconds = 0, bool $inverted = false): self
    {
        /** @psalm-suppress ImpureMethodCall */
        $interval = new \DateInterval('PT0S');
        $interval->invert = (int) $inverted;
        $interval->y = $years;
        $interval->m = $months;
        $interval->d = $weeks * 7 + $days;
        $interval->h = $hours;
        $interval->i = $minutes;
        $interval->s = $seconds;
        $interval->f = $microseconds / 1_000_000;

        return new self($interval);
    }

    /** @psalm-pure */
    public static function fromNative(\DateInterval $interval): self
    {
        return new self(clone $interval);
    }

    /** @psalm-pure */
    public static function cast(string|self|\DateInterval $duration): self
    {
        if ($duration instanceof self) {
            return $duration;
        }

        if ($duration instanceof \DateInterval) {
            return self::fromNative($duration);
        }

        return self::fromString($duration);
    }

    /** @psalm-pure */
    public static function years(int $years): self
    {
        return self::fromSinglePart('years', $years);
    }

    /** @psalm-pure */
    public static function months(int $months): self
    {
        return self::fromSinglePart('months', $months);
    }

    /** @psalm-pure */
    public static function weeks(int $weeks): self
    {
        return self::fromSinglePart('weeks', $weeks);
    }

    /** @psalm-pure */
    public static function days(int $days): self
    {
        return self::fromSinglePart('days', $days);
    }

    /** @psalm-pure */
    public static function hours(int $hours): self
    {
        return self::fromSinglePart('hours', $hours);
    }

    /** @psalm-pure */
    public static function minutes(int $minutes): self
    {
        return self::fromSinglePart('minutes', $minutes);
    }

    /** @psalm-pure */
    public static function seconds(int $seconds): self
    {
        return self::fromSinglePart('seconds', $seconds);
    }

    public function getYears(): int
    {
        return $this->interval->y;
    }

    public function getMonths(): int
    {
        return $this->interval->m;
    }

    public function getDays(): int
    {
        return $this->interval->d;
    }

    public function getHours(): int
    {
        return $this->interval->h;
    }

    public function getMinutes(): int
    {
        return $this->interval->i;
    }

    public function getSeconds(): int
    {
        return $this->interval->s;
    }

    public function getMicroseconds(): int
    {
        return (int) sprintf('%.0F', $this->interval->f * 1_000_000);
    }

    public function getTotalMonths(): int
    {
        return 12 * $this->interval->y + $this->interval->m;
    }

    public function getTotalDays(): ?int
    {
        return is_int($this->interval->days) ? $this->interval->days : null;
    }

    public function isInverted(): bool
    {
        return (bool) $this->interval->invert;
    }

    public function isZero(): bool
    {
        if ($this->interval->y || $this->interval->m || $this->interval->d) {
            return false;
        }
        if ($this->interval->h || $this->interval->i || $this->interval->s || $this->interval->f) {
            return false;
        }

        return true;
    }

    public function format(string $format): string
    {
        /** @psalm-suppress ImpureMethodCall */
        return $this->interval->format($format);
    }

    public function __toString(): string
    {
        return $this->toIso();
    }

    public function toIso(): string
    {
        $string = 'P';
        $time = '';

        if ($this->interval->y) {
            $string .= $this->interval->y.'Y';
        }
        if ($this->interval->m) {
            $string .= $this->interval->m.'M';
        }
        if ($this->interval->d) {
            $string .= $this->interval->d.'D';
        }

        if ($this->interval->h) {
            $time .= $this->interval->h.'H';
        }
        if ($this->interval->i) {
            $time .= $this->interval->i.'M';
        }
        $seconds = sprintf('%.6F', $this->interval->s + $this->interval->f);
        if ('0.000000' !== $seconds) {
            $time .= rtrim($seconds, '.0').'S';
        }

        if ($time) {
            $string .= 'T'.$time;
        }
        if ('P' === $string) {
            $string .= 'T0S';
        }
        if ($this->isInverted()) {
            $string = '-'.$string;
        }

        return $string;
    }

    public function toNative(): \DateInterval
    {
        return clone $this->interval;
    }

    /**
     * @param array{interval: \DateInterval} $data
     */
    public static function __set_state(array $data): self
    {
        return new self($data['interval']);
    }

    public function jsonSerialize(): string
    {
        return $this->toIso();
    }

    public function __serialize(): array
    {
        return ['interval' => $this->interval];
    }

    /**
     * @param array{interval: \DateInterval} $data
     */
    public function __unserialize(array $data): void
    {
        $this->interval = $data['interval'];
    }

    public function __debugInfo(): array
    {
        return [
            'inverted' => (bool) $this->interval->invert,
            'years' => $this->interval->y,
            'months' => $this->interval->m,
            'days' => $this->interval->d,
            'hours' => $this->interval->h,
            'minutes' => $this->interval->i,
            'seconds' => $this->interval->s,
            'microseconds' => $this->getMicroseconds(),
            'totalDays' => $this->getTotalDays(),
        ];
    }

    /** @psalm-pure */
    private static function fromSinglePart(string $part, int $count): self
    {
        if (1 !== $count) {
            return new self(\DateInterval::createFromDateString($count.$part));
        }

        /**
         * @var array<string, self> $durations
         * @psalm-suppress ImpureStaticVariable
         */
        static $durations = [];

        if (!isset($durations[$part])) {
            $durations[$part] = new self(\DateInterval::createFromDateString('1'.$part));
        }

        return $durations[$part];
    }
}
