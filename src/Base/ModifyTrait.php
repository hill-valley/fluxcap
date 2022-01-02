<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Base;

use HillValley\Fluxcap\Duration;

/**
 * @internal
 * @psalm-immutable
 */
trait ModifyTrait
{
    private readonly \DateTimeImmutable $dateTime;

    public function modify(string $modify): self
    {
        return self::fromNative($this->dateTime->modify($modify));
    }

    public function add(Duration $duration): self
    {
        return self::fromNative($this->dateTime->add($duration->toNative()));
    }

    public function sub(Duration $duration): self
    {
        return self::fromNative($this->dateTime->sub($duration->toNative()));
    }
}
