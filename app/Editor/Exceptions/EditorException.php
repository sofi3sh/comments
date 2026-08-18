<?php

namespace App\Editor\Exceptions;

use Exception;

final class EditorException extends Exception
{
    public static function invalidJson(): self
    {
        return new self('Invalid EditorJS JSON structure');
    }

    public static function unsupportedBlock(string $type): self
    {
        return new self("Unsupported block type: {$type}");
    }

    public static function rulesViolation(string $message): self
    {
        return new self($message);
    }
}