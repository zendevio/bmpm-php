<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Rules;

use function is_array;

use Zendevio\BMPM\Contracts\RuleLoaderInterface;
use Zendevio\BMPM\Enums\Language;
use Zendevio\BMPM\Enums\MatchAccuracy;
use Zendevio\BMPM\Enums\NameType;
use Zendevio\BMPM\Exceptions\RuleLoadException;

/**
 * Loads phonetic rules from JSON data files.
 */
final class RuleLoader implements RuleLoaderInterface
{
    /**
     * @var array<string, RuleSet>
     */
    private array $cache = [];

    /**
     * @var array<string, array<array{pattern: string, languages: int, accept: bool}>>
     */
    private array $languageRulesCache = [];

    public function __construct(
        private readonly string $dataPath,
    ) {}

    /**
     * Create with default data path.
     */
    public static function create(): self
    {
        return new self(__DIR__ . '/Data');
    }

    /**
     * {@inheritDoc}
     */
    public function loadRules(Language $language, NameType $nameType): RuleSet
    {
        $cacheKey = "rules:{$nameType->value}:{$language->ruleName()}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $filename = "rules_{$language->ruleName()}.json";
        $path = $this->buildPath($nameType, $filename);

        $ruleSet = $this->loadRuleSetFromFile($path);
        $this->cache[$cacheKey] = $ruleSet;

        return $ruleSet;
    }

    /**
     * {@inheritDoc}
     */
    public function loadFinalRules(
        Language $language,
        NameType $nameType,
        MatchAccuracy $accuracy,
    ): RuleSet {
        $prefix = $accuracy === MatchAccuracy::Exact ? 'exact' : 'approx';
        $cacheKey = "{$prefix}:{$nameType->value}:{$language->ruleName()}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $filename = "{$prefix}_{$language->ruleName()}.json";
        $path = $this->buildPath($nameType, $filename);

        $ruleSet = $this->loadRuleSetFromFile($path);
        $this->cache[$cacheKey] = $ruleSet;

        return $ruleSet;
    }

    /**
     * {@inheritDoc}
     */
    public function loadCommonRules(NameType $nameType, MatchAccuracy $accuracy): RuleSet
    {
        $prefix = $accuracy === MatchAccuracy::Exact ? 'exact' : 'approx';
        $cacheKey = "{$prefix}:common:{$nameType->value}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $filename = "{$prefix}_common.json";
        $path = $this->buildPath($nameType, $filename);

        $ruleSet = $this->loadRuleSetFromFile($path);
        $this->cache[$cacheKey] = $ruleSet;

        return $ruleSet;
    }

    /**
     * {@inheritDoc}
     */
    public function loadLanguageRules(NameType $nameType): array
    {
        $cacheKey = "lang:{$nameType->value}";

        if (isset($this->languageRulesCache[$cacheKey])) {
            return $this->languageRulesCache[$cacheKey];
        }

        $path = $this->buildPath($nameType, 'language_rules.json');

        $content = @file_get_contents($path);
        if ($content === false) {
            throw RuleLoadException::fileNotFound($path);
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw RuleLoadException::invalidJson($path, json_last_error_msg());
        }

        if (!is_array($data) || !isset($data['rules']) || !is_array($data['rules'])) {
            throw RuleLoadException::missingRequiredField('rules', $path);
        }

        /** @var array<array{pattern: string, languages: int, accept: bool}> $rules */
        $rules = $data['rules'];
        $this->languageRulesCache[$cacheKey] = $rules;

        return $rules;
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->languageRulesCache = [];
    }

    /**
     * Build the full path to a rule file.
     */
    private function buildPath(NameType $nameType, string $filename): string
    {
        return $this->dataPath . '/' . $nameType->directory() . '/' . $filename;
    }

    /**
     * Load a rule set from a JSON file.
     */
    private function loadRuleSetFromFile(string $path): RuleSet
    {
        // Check file existence first to avoid PHP warnings
        // that testing frameworks capture even with @ suppression
        if (!file_exists($path)) {
            return new RuleSet();
        }

        $content = file_get_contents($path);
        if ($content === false) {
            // Return empty rule set if file can't be read
            return new RuleSet();
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw RuleLoadException::invalidJson($path, json_last_error_msg());
        }

        if (!is_array($data) || !isset($data['rules']) || !is_array($data['rules'])) {
            throw RuleLoadException::missingRequiredField('rules', $path);
        }

        /** @var array{name?: string, rules: array<array<string, mixed>>} $data */
        return RuleSet::fromJson($data);
    }

    /**
     * Get the data path.
     */
    public function getDataPath(): string
    {
        return $this->dataPath;
    }
}
