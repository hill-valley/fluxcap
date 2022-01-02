<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Base;

/**
 * @internal
 * @psalm-immutable
 */
trait FormatTrait
{
    private readonly \DateTimeImmutable $dateTime;

    public function __toString(): string
    {
        return $this->toIso();
    }

    public function format(string $format): string
    {
        return $this->dateTime->format($format);
    }

    public function jsonSerialize(): string
    {
        return $this->toIso();
    }
}
