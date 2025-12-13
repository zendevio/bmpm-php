<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Engine;

use Exception;

use function in_array;
use function strlen;

use Zendevio\BMPM\Contracts\LanguageDetectorInterface;
use Zendevio\BMPM\Contracts\PhoneticEncoderInterface;
use Zendevio\BMPM\Contracts\RuleLoaderInterface;
use Zendevio\BMPM\Enums\Language;
use Zendevio\BMPM\Enums\MatchAccuracy;
use Zendevio\BMPM\Enums\NameType;
use Zendevio\BMPM\Rules\Rule;
use Zendevio\BMPM\Rules\RuleSet;
use Zendevio\BMPM\Util\PhoneticExpander;
use Zendevio\BMPM\Util\StringHelper;

/**
 * Core phonetic encoding engine implementing the Beider-Morse algorithm.
 */
final class PhoneticEngine implements PhoneticEncoderInterface
{
    /**
     * Name prefixes to handle specially (varies by name type).
     *
     * @var array<string, array<string>>
     */
    private const NAME_PREFIXES = [
        'gen' => [
            'abe', 'aben', 'abi', 'abou', 'abu', 'al', 'bar', 'ben', 'bou', 'bu',
            'd', 'da', 'dal', 'de', 'del', 'dela', 'della', 'des', 'di', 'dos', 'du',
            'el', 'la', 'le', 'ibn', 'van', 'von', 'ha', 'vanden', 'vander',
        ],
        'ash' => ['ben', 'bar', 'ha'],
        'sep' => [
            'abe', 'aben', 'abi', 'abou', 'abu', 'al', 'bar', 'ben', 'bou', 'bu',
            'd', 'da', 'dal', 'de', 'del', 'dela', 'della', 'des', 'di',
            'el', 'la', 'le', 'ibn', 'ha',
        ],
    ];

    /**
     * Leading phrase patterns to normalize.
     *
     * @var array<string>
     */
    private const LEADING_PHRASES = ['de la', 'van der', 'van den'];

    /**
     * Cached rule sets.
     *
     * @var array<string, RuleSet>
     */
    private array $ruleSetCache = [];

    public function __construct(
        private readonly RuleLoaderInterface $ruleLoader,
        private readonly LanguageDetectorInterface $languageDetector,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function encode(
        string $input,
        NameType $nameType = NameType::Generic,
        MatchAccuracy $accuracy = MatchAccuracy::Approximate,
        ?int $languageMask = null,
    ): string {
        $input = $this->preprocessInput($input, $nameType);

        if ($input === '') {
            return '';
        }

        // Detect language if not provided
        $languageMask ??= $this->languageDetector->detect($input, $nameType);

        // Handle multi-word names
        if (str_contains($input, ' ')) {
            return $this->encodeMultiWord($input, $nameType, $accuracy, $languageMask);
        }

        return $this->encodeSingleWord($input, $nameType, $accuracy, $languageMask);
    }

    /**
     * {@inheritDoc}
     */
    public function encodeToArray(
        string $input,
        NameType $nameType = NameType::Generic,
        MatchAccuracy $accuracy = MatchAccuracy::Approximate,
        ?int $languageMask = null,
    ): array {
        $result = $this->encode($input, $nameType, $accuracy, $languageMask);

        return PhoneticExpander::expand($result);
    }

    /**
     * {@inheritDoc}
     */
    public function encodeBatch(
        array $inputs,
        NameType $nameType = NameType::Generic,
        MatchAccuracy $accuracy = MatchAccuracy::Approximate,
        ?int $languageMask = null,
    ): array {
        $results = [];

        foreach ($inputs as $input) {
            $results[$input] = $this->encode($input, $nameType, $accuracy, $languageMask);
        }

        return $results;
    }

    /**
     * Preprocess input: normalize, handle prefixes, etc.
     */
    private function preprocessInput(string $input, NameType $nameType): string
    {
        try {
            $input = StringHelper::normalize($input);
        } catch (Exception) {
            return '';
        }

        // Remove spaces from certain leading phrases
        $input = StringHelper::removeLeadingPrefixes($input, self::LEADING_PHRASES);

        // For ash and gen: remove all apostrophes
        if ($nameType !== NameType::Sephardic) {
            $input = StringHelper::remove($input, "'");
        }

        // Handle multiple spaces/apostrophes/dashes: keep only first, remove rest
        $input = $this->normalizeDelimiters($input);

        return $input;
    }

    /**
     * Normalize delimiters: keep only the first space/apostrophe/dash.
     */
    private function normalizeDelimiters(string $input): string
    {
        $delimiters = ["'", '-', ' '];

        foreach ($delimiters as $delimiter) {
            $pos = strpos($input, $delimiter);
            if ($pos !== false) {
                // Remove all occurrences
                $input = str_replace($delimiter, '', $input);
                // Re-insert first occurrence as space
                $input = substr($input, 0, $pos) . ' ' . substr($input, $pos);
            }
        }

        return $input;
    }

    /**
     * Encode a multi-word name.
     */
    private function encodeMultiWord(
        string $input,
        NameType $nameType,
        MatchAccuracy $accuracy,
        int $languageMask,
    ): string {
        // splitFirst always returns non-null here since this method is only
        // called when str_contains($input, ' ') is true (see encode() method)
        /** @var array{0: string, 1: string} $parts */
        $parts = StringHelper::splitFirst($input, ' ');

        [$word1, $word2] = $parts;
        $prefixes = self::NAME_PREFIXES[$nameType->value] ?? self::NAME_PREFIXES['gen'];

        $isExact = $accuracy === MatchAccuracy::Exact;

        if ($isExact) {
            // For exact matching, just concatenate
            return $this->encodeSingleWord($word1 . $word2, $nameType, $accuracy, $languageMask);
        }

        // Re-detect language for individual words
        $lang2 = $this->languageDetector->detect($word2, $nameType);
        $langCombined = $this->languageDetector->detect($word1 . $word2, $nameType);

        if (in_array(strtolower($word1), $prefixes, true)) {
            // First word is a known prefix: encode Y and XY
            $result2 = $this->encodeSingleWord($word2, $nameType, $accuracy, $lang2);
            $resultCombined = $this->encodeSingleWord($word1 . $word2, $nameType, $accuracy, $langCombined);

            return PhoneticExpander::merge($result2, $resultCombined);
        }

        // First word is not a prefix: encode X, Y, and XY
        $lang1 = $this->languageDetector->detect($word1, $nameType);
        $result1 = $this->encodeSingleWord($word1, $nameType, $accuracy, $lang1);
        $result2 = $this->encodeSingleWord($word2, $nameType, $accuracy, $lang2);
        $resultCombined = $this->encodeSingleWord($word1 . $word2, $nameType, $accuracy, $langCombined);

        return PhoneticExpander::merge(
            PhoneticExpander::merge($result1, $result2),
            $resultCombined
        );
    }

    /**
     * Encode a single word.
     */
    private function encodeSingleWord(
        string $input,
        NameType $nameType,
        MatchAccuracy $accuracy,
        int $languageMask,
    ): string {
        // Determine which language's rules to use
        $language = $this->selectLanguage($languageMask, $nameType);

        // Get rule sets
        $rules = $this->getRuleSet('rules', $language, $nameType);
        $finalRules1 = $this->getCommonRuleSet($nameType, $accuracy);
        $finalRules2 = $this->getRuleSet($accuracy->value, $language, $nameType);

        // Apply language rules
        $phonetic = $this->applyRules($input, $rules, $languageMask);

        // Apply final rules
        $phonetic = $this->applyFinalRules($phonetic, $finalRules1, $languageMask, false);

        return $this->applyFinalRules($phonetic, $finalRules2, $languageMask, true);
    }

    /**
     * Select the primary language from a bitmask.
     */
    private function selectLanguage(int $languageMask, NameType $nameType): Language
    {
        $languages = Language::fromMask($languageMask);

        // Filter to languages available for this name type
        $available = Language::forNameType($nameType);
        $filtered = array_filter(
            $languages,
            static fn(Language $lang): bool => in_array($lang, $available, true)
        );

        if ($filtered !== []) {
            // Remove "Any" if there are more specific options
            $specific = array_filter($filtered, static fn(Language $l): bool => $l !== Language::Any);
            if ($specific !== []) {
                return reset($specific);
            }
        }

        return Language::Any;
    }

    /**
     * Apply phonetic rules to input.
     */
    private function applyRules(string $input, RuleSet $rules, int $languageMask): string
    {
        $inputLength = strlen($input);
        $phonetic = '';
        $i = 0;

        while ($i < $inputLength) {
            $found = false;

            foreach ($rules as $rule) {
                if (!$rule->matches($input, $i, $languageMask)) {
                    continue;
                }

                // Check attribute compatibility
                $candidate = $this->applyRuleIfCompatible($phonetic, $rule->phonetic, $languageMask);
                if ($candidate === false) {
                    continue;
                }

                $phonetic = $candidate;
                $found = true;
                $i += $rule->patternLength();

                break;
            }

            if (!$found) {
                // Character not in rules (e.g., space) - skip it
                $i++;
            }
        }

        return $phonetic;
    }

    /**
     * Apply final/approximation rules.
     */
    private function applyFinalRules(
        string $phonetic,
        RuleSet $rules,
        int $languageMask,
        bool $strip,
    ): string {
        if ($rules->isEmpty()) {
            return $phonetic;
        }

        // Expand any existing alternates
        $phonetic = PhoneticExpander::normalizeLanguageAttributes($phonetic, false);
        $alternatives = explode('|', PhoneticExpander::normalizeLanguageAttributes($phonetic, false));

        if (str_contains($phonetic, '(')) {
            $alternatives = PhoneticExpander::expand($phonetic);
        }

        $results = [];

        foreach ($alternatives as $alternative) {
            $processed = $this->processFinalRulesForAlternative($alternative, $rules, $languageMask);
            if ($processed !== '' && $processed !== '[0]') {
                $results[] = $processed;
            }
        }

        // Combine results
        $phonetic = implode('|', array_unique($results));

        if ($strip) {
            $phonetic = PhoneticExpander::normalizeLanguageAttributes($phonetic, true);
        }

        if (str_contains($phonetic, '|')) {
            return '(' . PhoneticExpander::removeDuplicates($phonetic) . ')';
        }

        return $phonetic;
    }

    /**
     * Process final rules for a single alternative.
     */
    private function processFinalRulesForAlternative(
        string $phonetic,
        RuleSet $rules,
        int $languageMask,
    ): string {
        $phoneticStripped = PhoneticExpander::normalizeLanguageAttributes($phonetic, true);
        $result = '';
        $i = 0;
        $length = strlen($phonetic);

        while ($i < $length) {
            // Skip language attributes
            if ($phonetic[$i] === '[') {
                $attrStart = $i;
                while ($i < $length && $phonetic[$i] !== ']') {
                    $i++;
                }
                if ($i < $length) {
                    $i++; // Skip closing bracket
                    $result .= substr($phonetic, $attrStart, $i - $attrStart);
                }

                continue;
            }

            $found = false;

            foreach ($rules as $rule) {
                // Match against stripped version for position calculation
                $strippedPos = strlen(PhoneticExpander::normalizeLanguageAttributes(
                    substr($phonetic, 0, $i),
                    true
                ));

                if (!$rule->matchesPattern($phoneticStripped, $strippedPos)) {
                    continue;
                }

                if (!$rule->matchesLeftContext($phoneticStripped, $strippedPos)) {
                    continue;
                }

                if (!$rule->matchesRightContext($phoneticStripped, $strippedPos)) {
                    continue;
                }

                if (!$rule->appliesToLanguage($languageMask)) {
                    continue;
                }

                $candidate = $this->applyRuleIfCompatible($result, $rule->phonetic, $languageMask);
                if ($candidate === false) {
                    continue;
                }

                $result = $candidate;
                $found = true;
                $i += $rule->patternLength();

                break;
            }

            if (!$found) {
                $result .= $phonetic[$i];
                $i++;
            }
        }

        return PhoneticExpander::normalizeLanguageAttributes($result, false);
    }

    /**
     * Apply a rule if its attributes are compatible.
     *
     * @return string|false
     */
    private function applyRuleIfCompatible(string $phonetic, string $target, int $languageMask): string|false
    {
        $candidate = $phonetic . $target;

        if (!str_contains($candidate, '[')) {
            return $candidate;
        }

        // Expand and check compatibility
        $expanded = PhoneticExpander::expand($candidate);
        $compatible = [];

        foreach ($expanded as $alt) {
            if ($languageMask !== 1) { // 1 = "any" language
                $alt = PhoneticExpander::normalizeLanguageAttributes($alt . '[' . $languageMask . ']', false);
            }

            if ($alt !== '[0]') {
                $compatible[] = $alt;
            }
        }

        if ($compatible === []) {
            return false;
        }

        return PhoneticExpander::collapse($compatible);
    }

    /**
     * Get a cached rule set.
     */
    private function getRuleSet(string $type, Language $language, NameType $nameType): RuleSet
    {
        $cacheKey = "{$type}:{$nameType->value}:{$language->ruleName()}";

        if (!isset($this->ruleSetCache[$cacheKey])) {
            $this->ruleSetCache[$cacheKey] = match ($type) {
                'rules' => $this->ruleLoader->loadRules($language, $nameType),
                'exact' => $this->ruleLoader->loadFinalRules($language, $nameType, MatchAccuracy::Exact),
                default => $this->ruleLoader->loadFinalRules($language, $nameType, MatchAccuracy::Approximate),
            };
        }

        return $this->ruleSetCache[$cacheKey];
    }

    /**
     * Get common rule set for a name type and accuracy.
     */
    private function getCommonRuleSet(NameType $nameType, MatchAccuracy $accuracy): RuleSet
    {
        $cacheKey = "common:{$accuracy->value}:{$nameType->value}";

        if (!isset($this->ruleSetCache[$cacheKey])) {
            $this->ruleSetCache[$cacheKey] = $this->ruleLoader->loadCommonRules($nameType, $accuracy);
        }

        return $this->ruleSetCache[$cacheKey];
    }

    /**
     * Clear all caches.
     */
    public function clearCache(): void
    {
        $this->ruleSetCache = [];
        $this->ruleLoader->clearCache();
    }
}
