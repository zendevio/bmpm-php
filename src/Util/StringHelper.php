<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Util;

use function mb_convert_encoding;
use function mb_detect_encoding;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;

use ValueError;
use Zendevio\BMPM\Exceptions\InvalidInputException;

/**
 * UTF-8 safe string manipulation utilities.
 */
class StringHelper
{
    private const MAX_INPUT_LENGTH = 1000;

    /**
     * Normalize input to UTF-8 and lowercase.
     *
     * @throws InvalidInputException If input is invalid
     */
    public static function normalize(string $input): string
    {
        $input = trim($input);

        if ($input === '') {
            throw InvalidInputException::emptyInput();
        }

        // Convert to UTF-8 if needed
        $input = self::toUtf8($input);

        // Check length
        $length = mb_strlen($input, 'UTF-8');
        if ($length > self::MAX_INPUT_LENGTH) {
            throw InvalidInputException::inputTooLong($length, self::MAX_INPUT_LENGTH);
        }

        // Decode HTML entities (including numeric entities like &#039;)
        if (str_contains($input, '&')) {
            $input = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Convert to lowercase
        return mb_strtolower($input, 'UTF-8');
    }

    /**
     * Convert string to UTF-8 encoding.
     *
     * @throws InvalidInputException If encoding conversion fails
     */
    public static function toUtf8(string $input): string
    {
        $encoding = mb_detect_encoding($input, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);

        if ($encoding === false) {
            $encoding = 'ISO-8859-1';
        }

        if ($encoding !== 'UTF-8') {
            try {
                return static::convertEncoding($input, $encoding);
            } catch (ValueError) {
                throw InvalidInputException::invalidEncoding($input);
            }
        }

        return $input;
    }

    /**
     * Perform the actual encoding conversion.
     * Protected for testability - can be overridden in tests to simulate errors.
     */
    protected static function convertEncoding(string $input, string $fromEncoding): string
    {
        /** @var string $result mb_convert_encoding always returns string in PHP 8.0+ */
        $result = mb_convert_encoding($input, 'UTF-8', $fromEncoding);

        return $result;
    }

    /**
     * Check if string contains only ASCII characters.
     */
    public static function isAscii(string $input): bool
    {
        return preg_match('/^[\x00-\x7F]*$/', $input) === 1;
    }

    /**
     * Get substring safely with UTF-8 support.
     */
    public static function substring(string $input, int $start, ?int $length = null): string
    {
        return mb_substr($input, $start, $length, 'UTF-8');
    }

    /**
     * Get string length with UTF-8 support.
     */
    public static function length(string $input): int
    {
        return mb_strlen($input, 'UTF-8');
    }

    /**
     * Check if string starts with pattern.
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /**
     * Check if string ends with pattern.
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /**
     * Remove all occurrences of a character from string.
     */
    public static function remove(string $input, string $char): string
    {
        return str_replace($char, '', $input);
    }

    /**
     * Remove leading prefixes from a string (case-insensitive).
     *
     * @param array<string> $prefixes
     */
    public static function removeLeadingPrefixes(string $input, array $prefixes): string
    {
        foreach ($prefixes as $prefix) {
            $prefixWithSpace = $prefix . ' ';
            if (self::startsWith($input, $prefixWithSpace)) {
                $cleanedPrefix = str_replace(' ', '', $prefix);

                return $cleanedPrefix . mb_substr($input, mb_strlen($prefix, 'UTF-8'), null, 'UTF-8');
            }
        }

        return $input;
    }

    /**
     * Get the position of first occurrence of needle in haystack.
     *
     * @return int|false Position or false if not found
     */
    public static function position(string $haystack, string $needle): int|false
    {
        return mb_strpos($haystack, $needle, 0, 'UTF-8');
    }

    /**
     * Replace first occurrence of search with replace.
     */
    public static function replaceFirst(string $haystack, string $search, string $replace): string
    {
        $pos = self::position($haystack, $search);
        if ($pos === false) {
            return $haystack;
        }

        return mb_substr($haystack, 0, $pos, 'UTF-8')
            . $replace
            . mb_substr($haystack, $pos + mb_strlen($search, 'UTF-8'), null, 'UTF-8');
    }

    /**
     * Split string by first occurrence of delimiter.
     *
     * @return array{0: string, 1: string}|null Array of [before, after] or null if delimiter not found
     */
    public static function splitFirst(string $input, string $delimiter): ?array
    {
        $pos = self::position($input, $delimiter);
        if ($pos === false) {
            return null;
        }

        return [
            mb_substr($input, 0, $pos, 'UTF-8'),
            mb_substr($input, $pos + mb_strlen($delimiter, 'UTF-8'), null, 'UTF-8'),
        ];
    }

    /**
     * Check if string matches a regex pattern.
     */
    public static function matches(string $input, string $pattern): bool
    {
        return preg_match($pattern . 'u', $input) === 1;
    }

    /**
     * Extract all matches for a regex pattern.
     *
     * @return array<string>
     */
    public static function matchAll(string $input, string $pattern): array
    {
        preg_match_all($pattern . 'u', $input, $matches);

        return $matches[0] ?? [];
    }
}
