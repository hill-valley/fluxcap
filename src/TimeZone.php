<?php

declare(strict_types=1);

namespace HillValley\Fluxcap;

use HillValley\Fluxcap\Exception\InvalidStringException;
use function get_class;
use function gettype;
use function is_object;
use function is_string;

/**
 * @psalm-immutable
 */
final class TimeZone implements \JsonSerializable
{
    private \DateTimeZone $timeZone;

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
        } catch (\Exception $exception) {
            throw InvalidStringException::create("Unknown time zone \"$timeZone\".");
        }

        return new self($native);
    }

    /** @psalm-pure */
    public static function fromNative(\DateTimeZone $timeZone): self
    {
        return new self($timeZone);
    }

    /**
     * @param string|self|\DateTimeZone $timeZone
     * @psalm-pure
     */
    public static function cast($timeZone): self
    {
        if ($timeZone instanceof self) {
            return $timeZone;
        }

        if ($timeZone instanceof \DateTimeZone) {
            return new self($timeZone);
        }

        if (is_string($timeZone)) {
            return self::fromString($timeZone);
        }

        // @codeCoverageIgnoreStart
        throw new \TypeError(sprintf(
            '%s(): Argument #1 must be of type %s, %s given',
            __METHOD__,
            implode('|', ['string', self::class, \DateTimeZone::class]),
            is_object($timeZone) ? get_class($timeZone) : gettype($timeZone),
        ));
        // @codeCoverageIgnoreEnd
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
