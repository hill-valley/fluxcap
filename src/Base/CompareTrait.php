<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Base;

use HillValley\Fluxcap\Duration;

/**
 * @internal
 * @psalm-immutable
 */
trait CompareTrait
{
    private readonly \DateTimeImmutable $dateTime;

    /** @psalm-pure */
    public static function min(self $object, self ...$objects): self
    {
        foreach ($objects as $other) {
            if ($other->lowerThan($object)) {
                $object = $other;
            }
        }

        return $object;
    }

    /** @psalm-pure */
    public static function max(self $object, self ...$objects): self
    {
        foreach ($objects as $other) {
            if ($other->greaterThan($object)) {
                $object = $other;
            }
        }

        return $object;
    }

    public function diff(self $other, bool $absolute = false): Duration
    {
        return Duration::fromNative($this->dateTime->diff($other->dateTime, $absolute));
    }

    public function compareTo(self $other): int
    {
        return $this->dateTime <=> $other->dateTime;
    }

    public function equals(self $other): bool
    {
        return 0 === $this->compareTo($other);
    }

    public function greaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function greaterEquals(self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    public function lowerThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    public function lowerEquals(self $other): bool
    {
        return $this->compareTo($other) <= 0;
    }
}
