<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Exceptions;

use function sprintf;

/**
 * Exception thrown when input validation fails.
 */
class InvalidInputException extends BeiderMorseException
{
    public static function emptyInput(): self
    {
        return new self('Input cannot be empty');
    }

    public static function invalidEncoding(string $input): self
    {
        return new self(
            sprintf('Input "%s" is not valid UTF-8', mb_substr($input, 0, 50))
        );
    }

    public static function inputTooLong(int $length, int $maxLength): self
    {
        return new self(
            sprintf('Input length %d exceeds maximum allowed length of %d', $length, $maxLength)
        );
    }
}
