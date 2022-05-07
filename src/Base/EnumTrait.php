<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Base;

use function count;

/**
 * @internal
 * @psalm-immutable
 */
trait EnumTrait
{
    /**
     * @return array<int, self>
     * @psalm-pure
     */
    public static function all(): array
    {
        $all = [];

        foreach (self::cases() as $case) {
            $all[$case->value] = $case;
        }

        return $all;
    }

    public function getIndex(): int
    {
        return $this->value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function diffToPrev(self $prev): int
    {
        if ($prev->value < $this->value) {
            return $this->value - $prev->value;
        }

        return count(self::cases()) - $prev->value + $this->value;
    }

    public function diffToNext(self $next): int
    {
        if ($next->value > $this->value) {
            return $next->value - $this->value;
        }

        return count(self::cases()) - $this->value + $next->value;
    }

    private function formatIntl(string $format): string
    {
        return IntlFormatter::formatTimestamp(strtotime($this->name), $format);
    }
}
