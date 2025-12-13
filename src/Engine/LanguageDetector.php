<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Engine;

use Zendevio\BMPM\Contracts\LanguageDetectorInterface;
use Zendevio\BMPM\Contracts\RuleLoaderInterface;
use Zendevio\BMPM\Enums\Language;
use Zendevio\BMPM\Enums\NameType;
use Zendevio\BMPM\Util\StringHelper;

/**
 * Detects the language(s) of a name based on spelling patterns.
 *
 * Uses pattern-matching rules to identify which language(s) a name
 * likely originates from based on characteristic letter combinations.
 */
final class LanguageDetector implements LanguageDetectorInterface
{
    /**
     * Cached language rules indexed by name type.
     *
     * @var array<string, array<array{pattern: string, languages: int, accept: bool}>>
     */
    private array $rulesCache = [];

    /**
     * Cached "all languages" bitmask per name type.
     *
     * @var array<string, int>
     */
    private array $allLanguagesCache = [];

    public function __construct(
        private readonly RuleLoaderInterface $ruleLoader,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function detect(string $name, NameType $nameType = NameType::Generic): int
    {
        $name = StringHelper::normalize($name);
        $allLanguages = $this->getAllLanguagesMask($nameType);
        $choicesRemaining = $allLanguages;

        $rules = $this->getLanguageRules($nameType);

        foreach ($rules as $rule) {
            $pattern = $rule['pattern'];
            $languages = $rule['languages'];
            $accept = $rule['accept'];

            if (preg_match($pattern . 'u', $name) === 1) {
                if ($accept) {
                    // Accept: name is in one of these languages and no others
                    $choicesRemaining &= $languages;
                } else {
                    // Reject: name is NOT in any of these languages
                    $choicesRemaining &= (~$languages) % ($allLanguages + 1);
                }
            }
        }

        // If no languages remaining, default to "any"
        if ($choicesRemaining === 0) {
            return Language::Any->value;
        }

        return $choicesRemaining;
    }

    /**
     * {@inheritDoc}
     */
    public function detectLanguages(string $name, NameType $nameType = NameType::Generic): array
    {
        $mask = $this->detect($name, $nameType);

        return Language::fromMask($mask);
    }

    /**
     * {@inheritDoc}
     */
    public function detectPrimary(string $name, NameType $nameType = NameType::Generic): Language
    {
        $languages = $this->detectLanguages($name, $nameType);

        // Filter out "Any" if there are more specific languages
        $specific = array_filter(
            $languages,
            static fn(Language $lang): bool => $lang !== Language::Any
        );

        if ($specific !== []) {
            // Return the first specific language (lowest bit set)
            return reset($specific);
        }

        return Language::Any;
    }

    /**
     * Get the combined bitmask of all available languages for a name type.
     */
    private function getAllLanguagesMask(NameType $nameType): int
    {
        $key = $nameType->value;

        if (!isset($this->allLanguagesCache[$key])) {
            $languages = Language::forNameType($nameType);
            $this->allLanguagesCache[$key] = Language::combineMask($languages);
        }

        return $this->allLanguagesCache[$key];
    }

    /**
     * Get language detection rules for a name type.
     *
     * @return array<array{pattern: string, languages: int, accept: bool}>
     */
    private function getLanguageRules(NameType $nameType): array
    {
        $key = $nameType->value;

        if (!isset($this->rulesCache[$key])) {
            $this->rulesCache[$key] = $this->ruleLoader->loadLanguageRules($nameType);
        }

        return $this->rulesCache[$key];
    }

    /**
     * Clear the internal caches.
     */
    public function clearCache(): void
    {
        $this->rulesCache = [];
        $this->allLanguagesCache = [];
    }
}
