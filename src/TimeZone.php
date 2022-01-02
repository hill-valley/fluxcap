<?php

declare(strict_types=1);

namespace HillValley\Fluxcap;

use HillValley\Fluxcap\Exception\InvalidStringException;

/**
 * @psalm-immutable
 */
final class TimeZone implements \JsonSerializable, \Stringable
{
    private readonly \DateTimeZone $timeZone;

    private function __construct(\DateTimeZone $timeZone)
    {
        $this->timeZone = $timeZone;
    }

    /** @psalm-mutation-free */
    public static function default(): self
    {
        /**
         * @var self|null $timeZone
         * @psalm-suppress ImpureStaticVariable
         */
        static $timeZone;

        /** @psalm-suppress ImpureFunctionCall */
        $name = date_default_timezone_get();

        if (null === $timeZone || $name !== $timeZone->getName()) {
            $timeZone = self::fromString($name);
        }

        return $timeZone;
    }

    /** @psalm-pure */
    public static function utc(): self
    {
        /**
         * @var self|null $timeZone
         * @psalm-suppress ImpureStaticVariable
         */
        static $timeZone;

        if (null === $timeZone) {
            $timeZone = self::fromString('UTC'); // @codeCoverageIgnore
        }

        return $timeZone;
    }

    /** @psalm-pure */
    public static function fromString(string $timeZone): self
    {
        if ('' === $timeZone) {
            throw InvalidStringException::create('The time zone string can not be empty.');
        }

        try {
            $native = new \DateTimeZone($timeZone);
        } catch (\Exception) {
            throw InvalidStringException::create("Unknown time zone \"$timeZone\".");
        }

        return new self($native);
    }

    /** @psalm-pure */
    public static function fromNative(\DateTimeZone $timeZone): self
    {
        return new self($timeZone);
    }

    /** @psalm-pure */
    public static function cast(string|self|\DateTimeZone $timeZone): self
    {
        if ($timeZone instanceof self) {
            return $timeZone;
        }

        if ($timeZone instanceof \DateTimeZone) {
            return new self($timeZone);
        }

        return self::fromString($timeZone);
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return $this->timeZone->getName();
    }

    public function equals(self $other): bool
    {
        return $this === $other || $this->getName() === $other->getName();
    }

    public function isDefault(): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return date_default_timezone_get() === $this->timeZone->getName();
    }

    public function isUtc(): bool
    {
        return 'UTC' === $this->timeZone->getName();
    }

    public function toNative(): \DateTimeZone
    {
        return $this->timeZone;
    }

    /**
     * @param array{timeZone: \DateTimeZone} $data
     */
    public static function __set_state(array $data): self
    {
        return new self($data['timeZone']);
    }

    public function jsonSerialize(): string
    {
        return $this->getName();
    }

    public function __serialize(): array
    {
        return ['name' => $this->getName()];
    }

    /**
     * @param array{name: string} $data
     */
    public function __unserialize(array $data): void
    {
        $this->timeZone = new \DateTimeZone($data['name']);
    }

    public function __debugInfo(): array
    {
        return [
            'name' => $this->getName(),
        ];
    }
}
