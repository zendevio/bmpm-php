<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Enums;

/**
 * Name type variants for phonetic matching.
 *
 * The Beider-Morse algorithm supports three variants optimized for different name origins:
 * - Generic: Supports 20 languages, suitable for general use
 * - Ashkenazic: Optimized for Ashkenazic Jewish names from Eastern Europe
 * - Sephardic: Optimized for Sephardic Jewish names from Spain, Portugal, and the Mediterranean
 */
enum NameType: string
{
    case Generic = 'gen';
    case Ashkenazic = 'ash';
    case Sephardic = 'sep';

    /**
     * Get the directory name for rule files.
     */
    public function directory(): string
    {
        return match ($this) {
            self::Generic => 'Generic',
            self::Ashkenazic => 'Ashkenazic',
            self::Sephardic => 'Sephardic',
        };
    }

    /**
     * Get a human-readable label for this name type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Generic => 'Generic',
            self::Ashkenazic => 'Ashkenazic',
            self::Sephardic => 'Sephardic',
        };
    }

    /**
     * Get a description of this name type.
     */
    public function description(): string
    {
        return match ($this) {
            self::Generic => 'General phonetic matching for 20 languages including Arabic, Cyrillic, Greek, Hebrew, and Latin-based languages',
            self::Ashkenazic => 'Optimized for Ashkenazic Jewish names from Central and Eastern Europe',
            self::Sephardic => 'Optimized for Sephardic Jewish names from Spain, Portugal, and Mediterranean regions',
        };
    }

    /**
     * Create from string value (case-insensitive).
     */
    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'gen', 'generic' => self::Generic,
            'ash', 'ashkenazic', 'ashkenazi' => self::Ashkenazic,
            'sep', 'sephardic', 'sephardi' => self::Sephardic,
            default => self::Generic,
        };
    }
}
