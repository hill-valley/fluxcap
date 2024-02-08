<?php

declare(strict_types=1);

namespace HillValley\Fluxcap\Exception;

use function strlen;

final class InvalidStringException extends \DomainException implements Exception
{
    private function __construct(string $message, ?\Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /** @internal */
    public static function create(string $message): self
    {
        return new self($message);
    }

    /** @internal */
    public static function wrap(\Exception $exception, string $prefix = 'DateTimeImmutable::__construct(): '): self
    {
        $message = $exception->getMessage();

        if (str_starts_with($message, $prefix)) {
            $message = substr($message, strlen($prefix));
        }

        $message = rtrim($message, '.').'.';

        return new self($message, $exception);
    }
}
