<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Exception;

final class FormatMismatchException extends \DomainException implements Exception
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    /** @internal */
    public static function create(string $format, string $dateTime): self
    {
        return new self(sprintf('The string "%s" does not match the format "%s".', $dateTime, $format));
    }
}
