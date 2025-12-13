<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Contracts;

use Zendevio\BMPM\Enums\Language;
use Zendevio\BMPM\Enums\MatchAccuracy;
use Zendevio\BMPM\Enums\NameType;
use Zendevio\BMPM\Rules\RuleSet;

/**
 * Contract for rule loading implementations.
 */
interface RuleLoaderInterface
{
    /**
     * Load phonetic rules for a specific language.
     *
     * @param Language $language The language to load rules for
     * @param NameType $nameType The name type variant
     *
     * @return RuleSet The loaded rule set
     */
    public function loadRules(Language $language, NameType $nameType): RuleSet;

    /**
     * Load final/approximation rules for a specific language.
     *
     * @param Language $language The language to load rules for
     * @param NameType $nameType The name type variant
     * @param MatchAccuracy $accuracy The matching accuracy mode
     *
     * @return RuleSet The loaded final rule set
     */
    public function loadFinalRules(
        Language $language,
        NameType $nameType,
        MatchAccuracy $accuracy,
    ): RuleSet;

    /**
     * Load common approximation rules.
     *
     * @param NameType $nameType The name type variant
     * @param MatchAccuracy $accuracy The matching accuracy mode
     *
     * @return RuleSet The loaded common rule set
     */
    public function loadCommonRules(NameType $nameType, MatchAccuracy $accuracy): RuleSet;

    /**
     * Load language detection rules.
     *
     * @param NameType $nameType The name type variant
     *
     * @return array<array{pattern: string, languages: int, accept: bool}> Language detection patterns
     */
    public function loadLanguageRules(NameType $nameType): array;

    /**
     * Clear any cached rules.
     */
    public function clearCache(): void;
}
