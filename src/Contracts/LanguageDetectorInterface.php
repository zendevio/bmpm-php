<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Contracts;

use Zendevio\BMPM\Enums\Language;
use Zendevio\BMPM\Enums\NameType;

/**
 * Contract for language detection implementations.
 */
interface LanguageDetectorInterface
{
    /**
     * Detect the language(s) of a name based on its spelling patterns.
     *
     * @param string $name The name to analyze
     * @param NameType $nameType The name type context for language detection
     *
     * @return int Bitmask of detected languages (use Language::fromMask() to decode)
     */
    public function detect(string $name, NameType $nameType = NameType::Generic): int;

    /**
     * Detect language(s) and return as Language enum array.
     *
     * @param string $name The name to analyze
     * @param NameType $nameType The name type context for language detection
     *
     * @return array<Language> Array of detected languages
     */
    public function detectLanguages(string $name, NameType $nameType = NameType::Generic): array;

    /**
     * Get the primary (most likely) detected language.
     *
     * @param string $name The name to analyze
     * @param NameType $nameType The name type context for language detection
     *
     * @return Language The primary detected language (defaults to Any if uncertain)
     */
    public function detectPrimary(string $name, NameType $nameType = NameType::Generic): Language;
}
