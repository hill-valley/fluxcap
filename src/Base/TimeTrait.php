<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Base;

use HillValley\Fluxcap\Duration;

/**
 * @internal
 * @psalm-immutable
 */
trait TimeTrait
{
    private readonly \DateTimeImmutable $dateTime;

    public function getHour(): int
    {
        return (int) $this->dateTime->format('G');
    }

    public function getMinute(): int
    {
        return (int) $this->dateTime->format('i');
    }

    public function getSecond(): int
    {
        return (int) $this->dateTime->format('s');
    }

    public function getMicrosecond(): int
    {
        return (int) $this->dateTime->format('u');
    }

    public function isMidnight(): bool
    {
        return '00:00:00.000000' === $this->format('H:i:s.u');
    }

    public function addHours(int $hours): self
    {
        return self::fromNative($this->dateTime->add(Duration::hours($hours)->toNative()));
    }

    public function subHours(int $hours): self
    {
        return self::fromNative($this->dateTime->sub(Duration::hours($hours)->toNative()));
    }

    public function addMinutes(int $minutes): self
    {
        return self::fromNative($this->dateTime->add(Duration::minutes($minutes)->toNative()));
    }

    public function subMinutes(int $minutes): self
    {
        return self::fromNative($this->dateTime->sub(Duration::minutes($minutes)->toNative()));
    }

    public function addSeconds(int $seconds): self
    {
        return self::fromNative($this->dateTime->add(Duration::seconds($seconds)->toNative()));
    }

    public function subSeconds(int $seconds): self
    {
        return self::fromNative($this->dateTime->sub(Duration::seconds($seconds)->toNative()));
    }
}
