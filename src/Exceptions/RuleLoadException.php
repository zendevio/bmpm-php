<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Exceptions;

use function sprintf;

/**
 * Exception thrown when rule loading fails.
 */
class RuleLoadException extends BeiderMorseException
{
    public static function fileNotFound(string $path): self
    {
        return new self(
            sprintf('Rule file not found: "%s"', $path)
        );
    }

    public static function invalidJson(string $path, string $error): self
    {
        return new self(
            sprintf('Invalid JSON in rule file "%s": %s', $path, $error)
        );
    }

    public static function invalidRuleFormat(string $path, string $error): self
    {
        return new self(
            sprintf('Invalid rule format in "%s": %s', $path, $error)
        );
    }

    public static function missingRequiredField(string $field, string $path): self
    {
        return new self(
            sprintf('Missing required field "%s" in rule file "%s"', $field, $path)
        );
    }
}
