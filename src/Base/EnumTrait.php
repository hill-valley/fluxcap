<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Base;

use HillValley\Fluxcap\Exception\InvalidPartException;
use function count;

/**
 * @internal
 * @psalm-immutable
 */
trait EnumTrait
{
    private int $index;

    private function __construct(int $index)
    {
        if ($index < 1 || $index > count(self::NAMES)) {
            throw InvalidPartException::create(sprintf('Index must be between 1 (%s) and %d (%s), got "%d".', self::NAMES[1], $count = count(self::NAMES), self::NAMES[$count], $index));
        }

        $this->index = $index;
    }

    /** @psalm-pure */
    public static function get(int $index): self
    {
        /**
         * @var array<int, self> $instances
         * @psalm-suppress ImpureStaticVariable
         */
        static $instances = [];

        if (!isset($instances[$index])) {
            $instances[$index] = new self($index);
        }

        return $instances[$index];
    }

    /**
     * @return array<int, self>
     * @psalm-pure
     */
    public static function all(): array
    {
        $all = [];

        foreach (self::NAMES as $index => $_) {
            $all[$index] = self::get($index);
        }

        return $all;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getName(): string
    {
        return self::NAMES[$this->index];
    }

    public function getAbbreviation(): string
    {
        return self::ABBREVIATIONS[$this->index];
    }

    public function equals(self $other): bool
    {
        return $this->index === $other->index;
    }

    public function diffToPrev(self $prev): int
    {
        if ($prev->index < $this->index) {
            return $this->index - $prev->index;
        }

        return count(self::NAMES) - $prev->index + $this->index;
    }

    public function diffToNext(self $next): int
    {
        if ($next->index > $this->index) {
            return $next->index - $this->index;
        }

        return count(self::NAMES) - $this->index + $next->index;
    }

    /**
     * @param array{index: int} $data
     */
    public static function __set_state(array $data): self
    {
        return self::get($data['index']);
    }

    public function jsonSerialize(): int
    {
        return $this->index;
    }

    public function __serialize(): array
    {
        return ['index' => $this->index];
    }

    /**
     * @param array{index: int} $data
     */
    public function __unserialize(array $data): void
    {
        $this->index = $data['index'];
    }

    public function __debugInfo(): array
    {
        return [
            'index' => $this->index,
            'name' => $this->getName(),
            'abbreviation' => $this->getAbbreviation(),
        ];
    }

    private function formatIntl(string $format): string
    {
        return IntlFormatter::formatTimestamp($this->toTimestamp(), $format);
    }

    private function toTimestamp(): int
    {
        return strtotime(self::NAMES[$this->index]);
    }
}
