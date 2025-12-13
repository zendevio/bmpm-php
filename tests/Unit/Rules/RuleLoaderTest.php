<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zendevio\BMPM\Enums\Language;
use Zendevio\BMPM\Enums\MatchAccuracy;
use Zendevio\BMPM\Enums\NameType;
use Zendevio\BMPM\Exceptions\RuleLoadException;
use Zendevio\BMPM\Rules\RuleLoader;
use Zendevio\BMPM\Rules\RuleSet;

#[CoversClass(RuleLoader::class)]
final class RuleLoaderTest extends TestCase
{
    private RuleLoader $loader;

    protected function setUp(): void
    {
        $this->loader = RuleLoader::create();
    }

    #[Test]
    public function it_creates_with_default_data_path(): void
    {
        $loader = RuleLoader::create();

        self::assertNotNull($loader);
        self::assertStringContainsString('Data', $loader->getDataPath());
    }

    #[Test]
    public function it_creates_with_custom_data_path(): void
    {
        $customPath = __DIR__ . '/../../../src/Rules/Data';
        $loader = new RuleLoader($customPath);

        self::assertSame($customPath, $loader->getDataPath());
    }

    #[Test]
    public function it_loads_rules_for_generic_english(): void
    {
        $ruleSet = $this->loader->loadRules(Language::English, NameType::Generic);

        self::assertInstanceOf(RuleSet::class, $ruleSet);
        self::assertFalse($ruleSet->isEmpty());
    }

    #[Test]
    public function it_loads_rules_for_generic_german(): void
    {
        $ruleSet = $this->loader->loadRules(Language::German, NameType::Generic);

        self::assertInstanceOf(RuleSet::class, $ruleSet);
        self::assertFalse($ruleSet->isEmpty());
    }

    #[Test]
    public function it_loads_rules_for_ashkenazic(): void
    {
        $ruleSet = $this->loader->loadRules(Language::German, NameType::Ashkenazic);

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_loads_rules_for_sephardic(): void
    {
        $ruleSet = $this->loader->loadRules(Language::Spanish, NameType::Sephardic);

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_loads_final_rules_approximate(): void
    {
        $ruleSet = $this->loader->loadFinalRules(
            Language::English,
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_loads_final_rules_exact(): void
    {
        $ruleSet = $this->loader->loadFinalRules(
            Language::English,
            NameType::Generic,
            MatchAccuracy::Exact
        );

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_loads_common_rules_approximate(): void
    {
        $ruleSet = $this->loader->loadCommonRules(
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_loads_common_rules_exact(): void
    {
        $ruleSet = $this->loader->loadCommonRules(
            NameType::Generic,
            MatchAccuracy::Exact
        );

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_loads_language_rules(): void
    {
        $rules = $this->loader->loadLanguageRules(NameType::Generic);

        self::assertIsArray($rules);
        self::assertNotEmpty($rules);
    }

    #[Test]
    public function it_loads_language_rules_for_ashkenazic(): void
    {
        $rules = $this->loader->loadLanguageRules(NameType::Ashkenazic);

        self::assertIsArray($rules);
    }

    #[Test]
    public function it_loads_language_rules_for_sephardic(): void
    {
        $rules = $this->loader->loadLanguageRules(NameType::Sephardic);

        self::assertIsArray($rules);
    }

    #[Test]
    public function it_caches_loaded_rules(): void
    {
        // Load twice
        $ruleSet1 = $this->loader->loadRules(Language::English, NameType::Generic);
        $ruleSet2 = $this->loader->loadRules(Language::English, NameType::Generic);

        // Should return same instance from cache
        self::assertSame($ruleSet1, $ruleSet2);
    }

    #[Test]
    public function it_clears_cache(): void
    {
        // Load to populate cache
        $this->loader->loadRules(Language::English, NameType::Generic);

        // Clear cache
        $this->loader->clearCache();

        // Load again - should get new instance
        $ruleSet = $this->loader->loadRules(Language::English, NameType::Generic);
        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_throws_for_missing_language_rules_file(): void
    {
        $loader = new RuleLoader('/nonexistent/path');

        $this->expectException(RuleLoadException::class);
        $loader->loadLanguageRules(NameType::Generic);
    }

    #[Test]
    public function it_loads_rules_for_any_language(): void
    {
        $ruleSet = $this->loader->loadRules(Language::Any, NameType::Generic);

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_returns_empty_ruleset_for_missing_optional_file(): void
    {
        // Arabic doesn't have approx rules in all modes
        $ruleSet = $this->loader->loadFinalRules(
            Language::Arabic,
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_caches_final_rules(): void
    {
        // Load twice
        $ruleSet1 = $this->loader->loadFinalRules(
            Language::German,
            NameType::Generic,
            MatchAccuracy::Approximate
        );
        $ruleSet2 = $this->loader->loadFinalRules(
            Language::German,
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Should return same instance from cache
        self::assertSame($ruleSet1, $ruleSet2);
    }

    #[Test]
    public function it_caches_common_rules(): void
    {
        // Load twice
        $ruleSet1 = $this->loader->loadCommonRules(
            NameType::Generic,
            MatchAccuracy::Approximate
        );
        $ruleSet2 = $this->loader->loadCommonRules(
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Should return same instance from cache
        self::assertSame($ruleSet1, $ruleSet2);
    }

    #[Test]
    public function it_caches_language_rules(): void
    {
        // Load twice
        $rules1 = $this->loader->loadLanguageRules(NameType::Generic);
        $rules2 = $this->loader->loadLanguageRules(NameType::Generic);

        // Should return same instance from cache
        self::assertSame($rules1, $rules2);
    }

    #[Test]
    public function it_loads_final_rules_for_ashkenazic(): void
    {
        $ruleSet = $this->loader->loadFinalRules(
            Language::German,
            NameType::Ashkenazic,
            MatchAccuracy::Approximate
        );

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_loads_final_rules_exact_for_ashkenazic(): void
    {
        $ruleSet = $this->loader->loadFinalRules(
            Language::German,
            NameType::Ashkenazic,
            MatchAccuracy::Exact
        );

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_loads_common_rules_for_ashkenazic(): void
    {
        $ruleSet = $this->loader->loadCommonRules(
            NameType::Ashkenazic,
            MatchAccuracy::Approximate
        );

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_loads_common_rules_exact_for_sephardic(): void
    {
        $ruleSet = $this->loader->loadCommonRules(
            NameType::Sephardic,
            MatchAccuracy::Exact
        );

        self::assertInstanceOf(RuleSet::class, $ruleSet);
    }

    #[Test]
    public function it_throws_for_invalid_json_in_language_rules(): void
    {
        $tempDir = sys_get_temp_dir() . '/bmpm_test_' . uniqid();
        mkdir($tempDir . '/Generic', 0o777, true);
        file_put_contents($tempDir . '/Generic/language_rules.json', 'invalid json {');

        $loader = new RuleLoader($tempDir);

        try {
            $this->expectException(RuleLoadException::class);
            $loader->loadLanguageRules(NameType::Generic);
        } finally {
            unlink($tempDir . '/Generic/language_rules.json');
            rmdir($tempDir . '/Generic');
            rmdir($tempDir);
        }
    }

    #[Test]
    public function it_throws_for_missing_rules_field_in_language_rules(): void
    {
        $tempDir = sys_get_temp_dir() . '/bmpm_test_' . uniqid();
        mkdir($tempDir . '/Generic', 0o777, true);
        file_put_contents($tempDir . '/Generic/language_rules.json', '{"data": []}');

        $loader = new RuleLoader($tempDir);

        try {
            $this->expectException(RuleLoadException::class);
            $loader->loadLanguageRules(NameType::Generic);
        } finally {
            unlink($tempDir . '/Generic/language_rules.json');
            rmdir($tempDir . '/Generic');
            rmdir($tempDir);
        }
    }

    #[Test]
    public function it_throws_for_invalid_json_in_rule_file(): void
    {
        $tempDir = sys_get_temp_dir() . '/bmpm_test_' . uniqid();
        mkdir($tempDir . '/Generic', 0o777, true);
        file_put_contents($tempDir . '/Generic/rules_any.json', 'not valid json');

        $loader = new RuleLoader($tempDir);

        try {
            $this->expectException(RuleLoadException::class);
            $loader->loadRules(Language::Any, NameType::Generic);
        } finally {
            unlink($tempDir . '/Generic/rules_any.json');
            rmdir($tempDir . '/Generic');
            rmdir($tempDir);
        }
    }

    #[Test]
    public function it_throws_for_missing_rules_field_in_rule_file(): void
    {
        $tempDir = sys_get_temp_dir() . '/bmpm_test_' . uniqid();
        mkdir($tempDir . '/Generic', 0o777, true);
        file_put_contents($tempDir . '/Generic/rules_any.json', '{"name": "test"}');

        $loader = new RuleLoader($tempDir);

        try {
            $this->expectException(RuleLoadException::class);
            $loader->loadRules(Language::Any, NameType::Generic);
        } finally {
            unlink($tempDir . '/Generic/rules_any.json');
            rmdir($tempDir . '/Generic');
            rmdir($tempDir);
        }
    }
}
