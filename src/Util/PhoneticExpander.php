<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Util;

use function count;
use function in_array;

/**
 * Utility for expanding phonetic alternates in parenthesized notation.
 *
 * The Beider-Morse algorithm produces output like "(a|b)c(d|e)" which represents
 * multiple alternatives: acd, ace, bcd, bce. This class expands such strings.
 */
final class PhoneticExpander
{
    /**
     * Expand a phonetic string with alternates into an array of all possibilities.
     *
     * @param string $phonetic String containing possible alternates like "(a|b)c"
     *
     * @return array<string> All expanded alternatives
     */
    public static function expand(string $phonetic): array
    {
        $result = self::expandSingle($phonetic);

        // Remove duplicates and empty values
        $result = array_filter(array_unique($result), static fn(string $s): bool => $s !== '' && $s !== '[0]');

        return array_values($result);
    }

    /**
     * Expand a single phonetic string.
     *
     * @return array<string>
     */
    private static function expandSingle(string $phonetic): array
    {
        // Normalize language attributes first
        $phonetic = self::normalizeLanguageAttributes($phonetic, false);

        // Find the first alternate group
        $altStart = strpos($phonetic, '(');
        if ($altStart === false) {
            return [$phonetic];
        }

        $prefix = substr($phonetic, 0, $altStart);

        // Find the matching closing parenthesis
        $altEnd = strpos($phonetic, ')', $altStart);
        if ($altEnd === false) {
            return [$phonetic]; // Malformed, return as-is
        }

        $altString = substr($phonetic, $altStart + 1, $altEnd - $altStart - 1);
        $suffix = substr($phonetic, $altEnd + 1);

        // Split alternatives
        $alternatives = explode('|', $altString);

        // Recursively expand each alternative with the suffix
        $results = [];
        foreach ($alternatives as $alt) {
            $expanded = self::expandSingle($prefix . $alt . $suffix);
            foreach ($expanded as $item) {
                if ($item !== '' && $item !== '[0]') {
                    $results[] = $item;
                }
            }
        }

        return $results;
    }

    /**
     * Convert expanded array back to parenthesized format.
     *
     * @param array<string> $alternatives
     */
    public static function collapse(array $alternatives): string
    {
        $alternatives = array_filter(array_unique($alternatives), static fn(string $s): bool => $s !== '');

        if ($alternatives === []) {
            return '';
        }

        if (count($alternatives) === 1) {
            return reset($alternatives);
        }

        return '(' . implode('|', $alternatives) . ')';
    }

    /**
     * Remove duplicate alternatives from a phonetic string.
     */
    public static function removeDuplicates(string $phonetic): string
    {
        // Handle pipe-delimited string
        if (str_contains($phonetic, '|')) {
            $parts = explode('|', $phonetic);
            $unique = [];

            foreach ($parts as $part) {
                if (!in_array($part, $unique, true)) {
                    $unique[] = $part;
                }
            }

            return implode('|', $unique);
        }

        return $phonetic;
    }

    /**
     * Normalize language attributes in a phonetic string.
     *
     * Removes all embedded bracketed attributes, logically-ANDs them together,
     * and optionally places them at the end.
     *
     * @param string $text The phonetic string to normalize
     * @param bool $strip If true, completely remove all attributes
     */
    public static function normalizeLanguageAttributes(string $text, bool $strip): string
    {
        $uninitialized = -1; // All 1's in bitmask
        $attrib = $uninitialized;

        while (($bracketStart = strpos($text, '[')) !== false) {
            $bracketEnd = strpos($text, ']', $bracketStart);
            if ($bracketEnd === false) {
                // Malformed, return as-is
                break;
            }

            $attrValue = substr($text, $bracketStart + 1, $bracketEnd - $bracketStart - 1);
            if (is_numeric($attrValue)) {
                $attrib &= (int) $attrValue;
            }

            $text = substr($text, 0, $bracketStart) . substr($text, $bracketEnd + 1);
        }

        if ($attrib === $uninitialized || $strip) {
            return $text;
        }

        return $text . '[' . $attrib . ']';
    }

    /**
     * Check if a phonetic string has alternates.
     */
    public static function hasAlternates(string $phonetic): bool
    {
        return str_contains($phonetic, '(') || str_contains($phonetic, '|');
    }

    /**
     * Count the number of alternatives in a phonetic string.
     */
    public static function countAlternatives(string $phonetic): int
    {
        return count(self::expand($phonetic));
    }

    /**
     * Merge two phonetic results, combining their alternatives.
     */
    public static function merge(string $a, string $b, string $separator = '-'): string
    {
        if ($a === '') {
            return $b;
        }
        if ($b === '') {
            return $a;
        }

        return $a . $separator . $b;
    }

    /**
     * Check if a phonetic encoding contains language attribute markers.
     */
    public static function hasLanguageAttributes(string $phonetic): bool
    {
        return str_contains($phonetic, '[');
    }

    /**
     * Strip all language attribute markers from a phonetic encoding.
     */
    public static function stripLanguageAttributes(string $phonetic): string
    {
        return self::normalizeLanguageAttributes($phonetic, true);
    }
}
