<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Enums;

/**
 * Supported languages for phonetic matching.
 *
 * Languages are represented as bit flags (powers of 2) to allow combining multiple
 * languages in a single bitmask for efficient language detection and rule application.
 *
 * Example:
 *   $languages = Language::German->value | Language::English->value;
 *   if ($languages & Language::German->value) { ... }
 */
enum Language: int
{
    case Any = 1;
    case Arabic = 2;
    case Cyrillic = 4;
    case Czech = 8;
    case Dutch = 16;
    case English = 32;
    case French = 64;
    case German = 128;
    case Greek = 256;
    case GreekLatin = 512;
    case Hebrew = 1024;
    case Hungarian = 2048;
    case Italian = 4096;
    case Latvian = 8192;
    case Polish = 16384;
    case Portuguese = 32768;
    case Romanian = 65536;
    case Russian = 131072;
    case Spanish = 262144;
    case Turkish = 524288;

    /**
     * Get all languages available in Generic mode.
     *
     * @return array<self>
     */
    public static function genericLanguages(): array
    {
        return [
            self::Any,
            self::Arabic,
            self::Cyrillic,
            self::Czech,
            self::Dutch,
            self::English,
            self::French,
            self::German,
            self::Greek,
            self::GreekLatin,
            self::Hebrew,
            self::Hungarian,
            self::Italian,
            self::Latvian,
            self::Polish,
            self::Portuguese,
            self::Romanian,
            self::Russian,
            self::Spanish,
            self::Turkish,
        ];
    }

    /**
     * Get all languages available in Ashkenazic mode.
     *
     * @return array<self>
     */
    public static function ashkenazicLanguages(): array
    {
        return [
            self::Any,
            self::Cyrillic,
            self::English,
            self::French,
            self::German,
            self::Hebrew,
            self::Hungarian,
            self::Polish,
            self::Romanian,
            self::Russian,
            self::Spanish,
        ];
    }

    /**
     * Get all languages available in Sephardic mode.
     *
     * @return array<self>
     */
    public static function sephardicLanguages(): array
    {
        return [
            self::Any,
            self::French,
            self::Hebrew,
            self::Italian,
            self::Portuguese,
            self::Spanish,
        ];
    }

    /**
     * Get languages for a specific name type.
     *
     * @return array<self>
     */
    public static function forNameType(NameType $nameType): array
    {
        return match ($nameType) {
            NameType::Generic => self::genericLanguages(),
            NameType::Ashkenazic => self::ashkenazicLanguages(),
            NameType::Sephardic => self::sephardicLanguages(),
        };
    }

    /**
     * Get the lowercase name for rule file references.
     */
    public function ruleName(): string
    {
        return match ($this) {
            self::Any => 'any',
            self::Arabic => 'arabic',
            self::Cyrillic => 'cyrillic',
            self::Czech => 'czech',
            self::Dutch => 'dutch',
            self::English => 'english',
            self::French => 'french',
            self::German => 'german',
            self::Greek => 'greek',
            self::GreekLatin => 'greeklatin',
            self::Hebrew => 'hebrew',
            self::Hungarian => 'hungarian',
            self::Italian => 'italian',
            self::Latvian => 'latvian',
            self::Polish => 'polish',
            self::Portuguese => 'portuguese',
            self::Romanian => 'romanian',
            self::Russian => 'russian',
            self::Spanish => 'spanish',
            self::Turkish => 'turkish',
        };
    }

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Any => 'Any',
            self::Arabic => 'Arabic',
            self::Cyrillic => 'Cyrillic',
            self::Czech => 'Czech',
            self::Dutch => 'Dutch',
            self::English => 'English',
            self::French => 'French',
            self::German => 'German',
            self::Greek => 'Greek',
            self::GreekLatin => 'Greek (Latin script)',
            self::Hebrew => 'Hebrew',
            self::Hungarian => 'Hungarian',
            self::Italian => 'Italian',
            self::Latvian => 'Latvian',
            self::Polish => 'Polish',
            self::Portuguese => 'Portuguese',
            self::Romanian => 'Romanian',
            self::Russian => 'Russian',
            self::Spanish => 'Spanish',
            self::Turkish => 'Turkish',
        };
    }

    /**
     * Create from string name (case-insensitive).
     */
    public static function fromString(string $name): ?self
    {
        $normalized = strtolower(trim($name));

        foreach (self::cases() as $case) {
            if ($case->ruleName() === $normalized) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Create from index position (0-based).
     */
    public static function fromIndex(int $index, NameType $nameType = NameType::Generic): ?self
    {
        $languages = self::forNameType($nameType);

        return $languages[$index] ?? null;
    }

    /**
     * Get the index position of this language for a name type.
     */
    public function index(NameType $nameType = NameType::Generic): int
    {
        $languages = self::forNameType($nameType);

        foreach ($languages as $index => $language) {
            if ($language === $this) {
                return $index;
            }
        }

        return 0; // Default to "any"
    }

    /**
     * Calculate combined bitmask for multiple languages.
     *
     * @param array<self> $languages
     */
    public static function combineMask(array $languages): int
    {
        $mask = 0;
        foreach ($languages as $language) {
            $mask |= $language->value;
        }

        return $mask;
    }

    /**
     * Get all languages included in a bitmask.
     *
     * @return array<self>
     */
    public static function fromMask(int $mask): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            if (($mask & $case->value) !== 0) {
                $result[] = $case;
            }
        }

        return $result;
    }

    /**
     * Check if this language is included in a bitmask.
     */
    public function isInMask(int $mask): bool
    {
        return ($mask & $this->value) !== 0;
    }
}
