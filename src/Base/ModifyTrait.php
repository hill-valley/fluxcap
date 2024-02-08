<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Base;

use HillValley\Fluxcap\Duration;
use HillValley\Fluxcap\Exception\InvalidStringException;

/**
 * @internal
 * @psalm-immutable
 */
trait ModifyTrait
{
    private readonly \DateTimeImmutable $dateTime;

    public function modify(string $modify): self
    {
        try {
            $dateTime = @$this->dateTime->modify($modify);
        } catch (\Exception $exception) { // @codeCoverageIgnore
            throw InvalidStringException::wrap($exception, 'DateTimeImmutable::modify(): '); // @codeCoverageIgnore
        }

        if (false === $dateTime) {
            throw InvalidStringException::create('Failed to parse time string ('.$modify.')');
        }

        return self::fromNative($dateTime);
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
