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
    public static function wrap(\Exception $exception): self
    {
        $message = $exception->getMessage();

        if (0 === strpos($message, $needle = 'DateTimeImmutable::__construct(): ')) {
            $message = substr($message, strlen($needle));
        }

        $message = rtrim($message, '.').'.';

        return new self($message, $exception);
    }
}
