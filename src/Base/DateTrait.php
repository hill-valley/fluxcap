<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Base;

use HillValley\Fluxcap\Duration;
use HillValley\Fluxcap\Month;
use HillValley\Fluxcap\Weekday;

/**
 * @internal
 * @psalm-immutable
 */
trait DateTrait
{
    private readonly \DateTimeImmutable $dateTime;

    abstract private function __construct(\DateTimeImmutable $dateTime);

    public function getYear(): int
    {
        return (int) $this->dateTime->format('Y');
    }

    public function getMonth(): int
    {
        return (int) $this->dateTime->format('n');
    }

    public function getQuarter(): int
    {
        return (int) ceil($this->getMonth() / 3);
    }

    public function getDay(): int
    {
        return (int) $this->dateTime->format('j');
    }

    public function getWeekday(): int
    {
        return (int) $this->dateTime->format('N');
    }

    public function isFirstDayOfYear(): bool
    {
        return '1-1' === $this->dateTime->format('n-j');
    }

    public function isLastDayOfYear(): bool
    {
        return '12-31' === $this->dateTime->format('n-j');
    }

    public function isFirstDayOfMonth(): bool
    {
        return '1' === $this->dateTime->format('j');
    }

    public function isLastDayOfMonth(): bool
    {
        return $this->dateTime->format('t') === $this->dateTime->format('j');
    }

    public function addYears(int $years): self
    {
        return new self($this->dateTime->add(Duration::years($years)->toNative()));
    }

    public function subYears(int $years): self
    {
        return new self($this->dateTime->sub(Duration::years($years)->toNative()));
    }

    public function addMonths(int $months): self
    {
        return new self($this->dateTime->add(Duration::months($months)->toNative()));
    }

    public function subMonths(int $months): self
    {
        return new self($this->dateTime->sub(Duration::months($months)->toNative()));
    }

    public function addWeeks(int $weeks): self
    {
        return new self($this->dateTime->add(Duration::weeks($weeks)->toNative()));
    }

    public function subWeeks(int $weeks): self
    {
        return new self($this->dateTime->sub(Duration::weeks($weeks)->toNative()));
    }

    public function addDays(int $days): self
    {
        return new self($this->dateTime->add(Duration::days($days)->toNative()));
    }

    public function subDays(int $days): self
    {
        return new self($this->dateTime->sub(Duration::days($days)->toNative()));
    }

    public function toFirstDayOfYear(): self
    {
        return new self($this->dateTime->setDate($this->getYear(), 1, 1));
    }

    public function toLastDayOfYear(): self
    {
        return new self($this->dateTime->setDate($this->getYear(), 12, 31));
    }

    public function toFirstDayOfQuarter(): self
    {
        return new self($this->dateTime->setDate($this->getYear(), ($this->getQuarter() - 1) * 3 + 1, 1));
    }

    public function toLastDayOfQuarter(): self
    {
        $quarter = $this->getQuarter();

        return new self($this->dateTime->setDate($this->getYear(), $quarter * 3, [1 => 31, 2 => 30, 3 => 30, 4 => 31][$quarter]));
    }

    public function toFirstDayOfMonth(): self
    {
        return new self($this->dateTime->setDate($this->getYear(), $this->getMonth(), 1));
    }

    public function toLastDayOfMonth(): self
    {
        return new self($this->dateTime->setDate($this->getYear(), $this->getMonth(), (int) $this->format('t')));
    }

    public function toMonth(): Month
    {
        return Month::from($this->getMonth());
    }

    public function toWeekday(): Weekday
    {
        return Weekday::from($this->getWeekday());
    }

    public function toPrevWeekday(Weekday $weekday): self
    {
        $current = $this->toWeekday();

        if ($current === $weekday) {
            return $this;
        }

        return $this->subDays($current->diffToPrev($weekday));
    }

    public function toNextWeekday(Weekday $weekday): self
    {
        $current = $this->toWeekday();

        if ($current === $weekday) {
            return $this;
        }

        return $this->addDays($current->diffToNext($weekday));
    }
}
