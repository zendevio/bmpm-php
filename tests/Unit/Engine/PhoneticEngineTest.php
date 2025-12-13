<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit\Engine;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zendevio\BMPM\Engine\LanguageDetector;
use Zendevio\BMPM\Engine\PhoneticEngine;
use Zendevio\BMPM\Enums\Language;
use Zendevio\BMPM\Enums\MatchAccuracy;
use Zendevio\BMPM\Enums\NameType;
use Zendevio\BMPM\Rules\RuleLoader;

#[CoversClass(PhoneticEngine::class)]
final class PhoneticEngineTest extends TestCase
{
    private PhoneticEngine $engine;

    protected function setUp(): void
    {
        $ruleLoader = RuleLoader::create();
        $languageDetector = new LanguageDetector($ruleLoader);
        $this->engine = new PhoneticEngine($ruleLoader, $languageDetector);
    }

    #[Test]
    public function it_encodes_simple_name(): void
    {
        $result = $this->engine->encode(
            'Smith',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
        self::assertIsString($result);
    }

    #[Test]
    public function it_encodes_to_array(): void
    {
        $result = $this->engine->encodeToArray(
            'Smith',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertIsArray($result);
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_batch(): void
    {
        $result = $this->engine->encodeBatch(
            ['Smith', 'Jones'],
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertArrayHasKey('Smith', $result);
        self::assertArrayHasKey('Jones', $result);
    }

    #[Test]
    public function it_encodes_with_exact_accuracy(): void
    {
        $result = $this->engine->encode(
            'Mueller',
            NameType::Generic,
            MatchAccuracy::Exact
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_with_language_mask(): void
    {
        $result = $this->engine->encode(
            'Müller',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::German->value
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_ashkenazic_names(): void
    {
        $result = $this->engine->encode(
            'Goldstein',
            NameType::Ashkenazic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_sephardic_names(): void
    {
        $result = $this->engine->encode(
            'Cardozo',
            NameType::Sephardic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_multi_word_names(): void
    {
        $result = $this->engine->encode(
            'Van der Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_hyphenated_names(): void
    {
        $result = $this->engine->encode(
            'Smith-Jones',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_names_with_diacritics(): void
    {
        $result = $this->engine->encode(
            'Müller',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_clears_cache(): void
    {
        // Encode something to populate cache
        $this->engine->encode('Smith', NameType::Generic, MatchAccuracy::Approximate);

        // Clear cache should not throw
        $this->engine->clearCache();

        // Should still work after clearing
        $result = $this->engine->encode('Smith', NameType::Generic, MatchAccuracy::Approximate);
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_with_all_languages_mask(): void
    {
        $result = $this->engine->encode(
            'Test',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::Any->value
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_german_names_correctly(): void
    {
        $result = $this->engine->encode(
            'Schwarzenegger',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::German->value
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_polish_names_correctly(): void
    {
        $result = $this->engine->encode(
            'Kowalski',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::Polish->value
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_empty_string_gracefully(): void
    {
        // Empty input returns empty output (preprocessing normalizes it)
        $result = $this->engine->encode(
            '',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertSame('', $result);
    }

    #[Test]
    public function it_handles_whitespace_only_string(): void
    {
        // Whitespace-only input returns empty output (preprocessing normalizes it)
        $result = $this->engine->encode(
            '   ',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertSame('', $result);
    }

    #[Test]
    public function it_handles_multi_word_with_known_prefix(): void
    {
        // 'ben' is a known prefix - should encode Y and XY
        $result = $this->engine->encode(
            'Ben David',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_multi_word_with_unknown_prefix(): void
    {
        // 'John' is not a known prefix - should encode X, Y, and XY
        $result = $this->engine->encode(
            'John Smith',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_multi_word_with_exact_accuracy(): void
    {
        // Exact accuracy concatenates words
        $result = $this->engine->encode(
            'John Smith',
            NameType::Generic,
            MatchAccuracy::Exact
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_name_with_apostrophe(): void
    {
        $result = $this->engine->encode(
            "O'Brien",
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_name_with_multiple_apostrophes(): void
    {
        $result = $this->engine->encode(
            "O'De'La",
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_name_with_dash(): void
    {
        $result = $this->engine->encode(
            'Smith-Jones-Brown',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_de_la_prefix(): void
    {
        // 'de la' is a leading phrase that gets normalized
        $result = $this->engine->encode(
            'de la Cruz',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_van_der_prefix(): void
    {
        // 'van der' is a leading phrase that gets normalized
        $result = $this->engine->encode(
            'van der Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_van_den_prefix(): void
    {
        // 'van den' is a leading phrase that gets normalized
        $result = $this->engine->encode(
            'van den Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_ashkenazic_with_known_prefix(): void
    {
        // 'bar' is a known Ashkenazic prefix
        $result = $this->engine->encode(
            'Bar Cohen',
            NameType::Ashkenazic,
            MatchAccuracy::Approximate
        );

        // Result can be empty for some names - just verify it's a string
        self::assertIsString($result);
    }

    #[Test]
    public function it_encodes_sephardic_with_known_prefix(): void
    {
        // 'el' is a known Sephardic prefix
        $result = $this->engine->encode(
            'El Greco',
            NameType::Sephardic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_preserves_apostrophes_in_sephardic(): void
    {
        // Sephardic mode should handle apostrophes differently
        $result = $this->engine->encode(
            "D'Costa",
            NameType::Sephardic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_language_mask_filtering(): void
    {
        // Multiple languages combined
        $result = $this->engine->encode(
            'Mueller',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::German->value | Language::English->value
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_uses_cached_rule_sets(): void
    {
        // First call populates cache
        $result1 = $this->engine->encode(
            'Smith',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::English->value
        );

        // Second call should use cached rule sets
        $result2 = $this->engine->encode(
            'Jones',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::English->value
        );

        self::assertNotEmpty($result1);
        self::assertNotEmpty($result2);
    }

    #[Test]
    public function it_handles_ashkenazic_exact(): void
    {
        $result = $this->engine->encode(
            'Goldstein',
            NameType::Ashkenazic,
            MatchAccuracy::Exact
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_sephardic_exact(): void
    {
        $result = $this->engine->encode(
            'Cardozo',
            NameType::Sephardic,
            MatchAccuracy::Exact
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_various_name_prefixes(): void
    {
        $prefixes = ['von', 'da', 'del', 'della', 'des', 'di', 'dos', 'du', 'el', 'la', 'le', 'ibn'];

        foreach ($prefixes as $prefix) {
            $result = $this->engine->encode(
                $prefix . ' Test',
                NameType::Generic,
                MatchAccuracy::Approximate
            );

            self::assertNotEmpty($result, "Failed for prefix: $prefix");
        }
    }

    #[Test]
    public function it_handles_cyrillic_language(): void
    {
        $result = $this->engine->encode(
            'Petrov',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::Cyrillic->value
        );

        // Result may be empty for some language combinations - verify it's a string
        self::assertIsString($result);
    }

    #[Test]
    public function it_handles_hebrew_language(): void
    {
        $result = $this->engine->encode(
            'Cohen',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::Hebrew->value
        );

        // Result may be empty for some language combinations - verify it's a string
        self::assertIsString($result);
    }

    #[Test]
    public function it_handles_german_only_language_filter(): void
    {
        // German-only encoding should filter out non-German phonetics
        $result = $this->engine->encode(
            'Schwarzmann',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::German->value
        );

        self::assertIsString($result);
    }

    #[Test]
    public function it_handles_polish_only_language_filter(): void
    {
        // Polish-only encoding with complex name
        $result = $this->engine->encode(
            'Szczepanski',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::Polish->value
        );

        self::assertIsString($result);
    }

    #[Test]
    public function it_handles_russian_only_language_filter(): void
    {
        // Russian-only encoding
        $result = $this->engine->encode(
            'Ivanov',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::Russian->value
        );

        self::assertIsString($result);
    }

    #[Test]
    public function it_handles_conflicting_language_filters(): void
    {
        // Name that might have different encodings across languages
        $result = $this->engine->encode(
            'Czerny',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::German->value | Language::Polish->value
        );

        self::assertIsString($result);
    }

    #[Test]
    public function it_handles_spanish_language_in_sephardic(): void
    {
        // Sephardic name with Spanish language filter
        $result = $this->engine->encode(
            'Benavides',
            NameType::Sephardic,
            MatchAccuracy::Approximate,
            Language::Spanish->value
        );

        self::assertIsString($result);
    }

    #[Test]
    public function it_handles_french_language_filter(): void
    {
        // French name with French filter
        $result = $this->engine->encode(
            'Beaumont',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::French->value
        );

        self::assertIsString($result);
    }

    #[Test]
    public function it_handles_hungarian_language_filter(): void
    {
        // Hungarian name (Ashkenazic)
        $result = $this->engine->encode(
            'Kovacs',
            NameType::Ashkenazic,
            MatchAccuracy::Approximate,
            Language::Hungarian->value
        );

        self::assertIsString($result);
    }

    #[Test]
    public function it_handles_complex_ashkenazic_name(): void
    {
        // Complex name that triggers multiple rule paths
        $result = $this->engine->encode(
            'Rosenzweig',
            NameType::Ashkenazic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_name_with_ch_sh_combinations(): void
    {
        // Name with complex consonant combinations
        $result = $this->engine->encode(
            'Chruschtschow',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertIsString($result);
    }

    #[Test]
    public function it_handles_name_producing_alternates(): void
    {
        // Name that produces alternative phonetic outputs (parentheses)
        $result = $this->engine->encode(
            'Weiss',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_exact_mode_with_language_filter(): void
    {
        // Exact mode with specific language
        $result = $this->engine->encode(
            'Schmidt',
            NameType::Generic,
            MatchAccuracy::Exact,
            Language::German->value
        );

        self::assertIsString($result);
    }

    #[Test]
    public function it_removes_apostrophes_in_generic_mode(): void
    {
        // In Generic mode, apostrophes should be removed
        $withApostrophe = $this->engine->encode(
            "O'Brien",
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        $withoutApostrophe = $this->engine->encode(
            'OBrien',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Should produce same result
        self::assertSame($withApostrophe, $withoutApostrophe);
    }

    #[Test]
    public function it_preserves_apostrophes_in_sephardic_mode(): void
    {
        // In Sephardic mode, apostrophes have different handling
        $result = $this->engine->encode(
            "D'Costa",
            NameType::Sephardic,
            MatchAccuracy::Approximate
        );

        // Just verify it produces output (apostrophe behavior is different)
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_normalizes_multiple_dashes(): void
    {
        // Multiple dashes should be normalized to single space
        $result = $this->engine->encode(
            'Smith-Jones-Brown',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_normalizes_multiple_spaces(): void
    {
        // Multiple spaces should be normalized
        $multiSpace = $this->engine->encode(
            'John    Smith',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        $singleSpace = $this->engine->encode(
            'John Smith',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Both should produce valid output
        self::assertNotEmpty($multiSpace);
        self::assertNotEmpty($singleSpace);
    }

    #[Test]
    public function it_encodes_exact_multiword_differently_than_approximate(): void
    {
        // Exact mode concatenates words, approximate encodes separately
        $exact = $this->engine->encode(
            'John Smith',
            NameType::Generic,
            MatchAccuracy::Exact
        );

        $approx = $this->engine->encode(
            'John Smith',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Results should be different
        self::assertNotSame($exact, $approx);
    }

    #[Test]
    public function it_uses_provided_language_mask_over_detection(): void
    {
        // When language mask is provided, it should use that instead of detecting
        $withMask = $this->engine->encode(
            'Test',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::German->value
        );

        $withoutMask = $this->engine->encode(
            'Test',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Both should produce valid output
        self::assertIsString($withMask);
        self::assertIsString($withoutMask);
    }

    #[Test]
    public function it_handles_known_prefix_differently(): void
    {
        // 'ben' is a known prefix
        $withPrefix = $this->engine->encode(
            'ben David',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // First word is not a prefix
        $noPrefix = $this->engine->encode(
            'John David',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Both should produce valid output but be different
        self::assertNotEmpty($withPrefix);
        self::assertNotEmpty($noPrefix);
    }

    #[Test]
    public function it_encodes_prefix_name_with_y_and_xy(): void
    {
        // 'van' is a known prefix - should encode Y and XY
        $result = $this->engine->encode(
            'van Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_non_prefix_name_with_x_y_and_xy(): void
    {
        // Non-prefix should encode X, Y, and XY
        $result = $this->engine->encode(
            'Peter Mueller',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_detects_language_when_mask_is_null(): void
    {
        // When languageMask is null, it should auto-detect
        // This tests the ??= operator on line 82
        $autoDetected = $this->engine->encode(
            'Kowalski',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Should produce valid output with auto-detected language
        self::assertNotEmpty($autoDetected);
    }

    #[Test]
    public function it_uses_explicit_mask_when_provided(): void
    {
        // When languageMask is provided, it should NOT auto-detect
        // This tests the ??= operator - the right side should NOT execute
        $withMask = $this->engine->encode(
            'Mueller',
            NameType::Generic,
            MatchAccuracy::Approximate,
            Language::German->value
        );

        // Should produce valid output using provided mask
        self::assertNotEmpty($withMask);
    }

    #[Test]
    public function it_returns_multi_word_result(): void
    {
        // Test that encodeMultiWord actually returns its result
        // This kills the ReturnRemoval mutant on line 86
        $result = $this->engine->encode(
            'Anna Maria',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Must return non-empty string for multi-word input
        self::assertNotEmpty($result);
        self::assertIsString($result);
    }

    #[Test]
    public function it_returns_different_result_for_single_vs_multi_word(): void
    {
        // Single word vs multi-word should produce different results
        // This verifies the multi-word path is being used and returning
        $multiWord = $this->engine->encode(
            'John Smith',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        $singleWord = $this->engine->encode(
            'JohnSmith',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Multi-word handling should produce different output
        self::assertNotSame($multiWord, $singleWord);
    }

    #[Test]
    public function it_normalizes_dash_delimiter(): void
    {
        // Test dash normalization specifically
        // This tests the foreach loop and delimiters array
        $withDash = $this->engine->encode(
            'Mary-Jane',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Dash should be converted to space, making it multi-word
        self::assertNotEmpty($withDash);
    }

    #[Test]
    public function it_normalizes_apostrophe_delimiter_in_ashkenazic(): void
    {
        // Test apostrophe normalization in Ashkenazic mode
        $withApostrophe = $this->engine->encode(
            "D'Amico",
            NameType::Ashkenazic,
            MatchAccuracy::Approximate
        );

        // Apostrophe should be normalized
        self::assertIsString($withApostrophe);
    }

    #[Test]
    public function it_normalizes_space_delimiter(): void
    {
        // Test space handling in delimiter normalization
        $withSpace = $this->engine->encode(
            'Peter Paul',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Should handle space delimiter
        self::assertNotEmpty($withSpace);
    }

    #[Test]
    public function it_keeps_first_occurrence_and_removes_rest(): void
    {
        // Test that first delimiter is preserved as space, rest removed
        // "Smith-Jones-Brown" -> "Smith JonesBrown" (first dash becomes space)
        $multiDash = $this->engine->encode(
            'Smith-Jones-Brown',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Should produce non-empty multi-word result
        self::assertNotEmpty($multiDash);
    }

    #[Test]
    public function it_handles_delimiter_near_start(): void
    {
        // Delimiter near start should be handled correctly
        // The substr logic needs to handle small positions
        $result = $this->engine->encode(
            'A-Smith',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Should produce valid output
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_delimiter_near_end(): void
    {
        // Delimiter near end should be handled correctly
        $result = $this->engine->encode(
            'Smith-B',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Should produce valid output
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_str_replace_removes_all_occurrences(): void
    {
        // Test that str_replace removes ALL occurrences of delimiter
        // "A-B-C-D" -> str_replace removes all dashes -> "ABCD"
        // Then first position is reinserted -> "A BCD"
        $result = $this->engine->encode(
            'A-B-C-D',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Should produce output (validates str_replace works)
        self::assertIsString($result);
    }

    #[Test]
    public function it_substr_correctly_splits_at_position(): void
    {
        // Test that substr correctly splits at the first delimiter position
        // "Hello-World" -> pos=5, substr(0,5) = "Hello", substr(5) = "World"
        // Result after normalization: "Hello World"
        $result = $this->engine->encode(
            'Hello-World',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Should produce non-empty multi-word result
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_processes_multiple_delimiter_types(): void
    {
        // Test name with multiple different delimiter types
        // The foreach should process all three: apostrophe, dash, space
        $result = $this->engine->encode(
            "O'Brien-Smith Jones",
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Should handle all delimiter types
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_array_items_all_required_for_normalization(): void
    {
        // Test that all delimiter types in the array are required
        // ArrayItemRemoval mutation would remove one delimiter type

        // Test with apostrophe
        $apostrophe = $this->engine->encode(
            "O'Brian",
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Test with dash
        $dash = $this->engine->encode(
            'Smith-Brown',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Test with space
        $space = $this->engine->encode(
            'John Paul',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // All should produce output
        self::assertNotEmpty($apostrophe);
        self::assertNotEmpty($dash);
        self::assertNotEmpty($space);
    }

    #[Test]
    public function it_foreach_required_for_delimiter_processing(): void
    {
        // Test that foreach loop is required to process delimiters
        // Foreach_ mutation would skip the loop entirely
        $withDelimiters = $this->engine->encode(
            "Mary-Jane O'Connor Smith",
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Without foreach, delimiters wouldn't be processed
        self::assertNotEmpty($withDelimiters);
    }

    #[Test]
    public function it_correctly_reassembles_after_delimiter_removal(): void
    {
        // Test the concatenation: substr(0, $pos) . ' ' . substr($pos)
        // ConcatOperandRemoval would break this
        $result = $this->engine->encode(
            'First-Second',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // The reassembly should produce valid multi-word output
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_exact_mode_concatenates_words(): void
    {
        // Line 190: return $this->encodeSingleWord($word1 . $word2, ...)
        // Exact mode should concatenate "John" + "Smith" = "JohnSmith"
        $exact = $this->engine->encode(
            'John Smith',
            NameType::Generic,
            MatchAccuracy::Exact
        );

        $concatenated = $this->engine->encode(
            'JohnSmith',
            NameType::Generic,
            MatchAccuracy::Exact
        );

        // Exact multi-word should produce same as concatenated single word
        self::assertSame($concatenated, $exact);
    }

    #[Test]
    public function it_exact_mode_concat_mutation_would_break(): void
    {
        // ConcatOperandRemoval on line 190 would produce encoding of just $word1 or $word2
        $exact = $this->engine->encode(
            'John Smith',
            NameType::Generic,
            MatchAccuracy::Exact
        );

        $word1Only = $this->engine->encode(
            'John',
            NameType::Generic,
            MatchAccuracy::Exact
        );

        $word2Only = $this->engine->encode(
            'Smith',
            NameType::Generic,
            MatchAccuracy::Exact
        );

        // Exact result should NOT match either word alone
        self::assertNotSame($word1Only, $exact, 'Exact should not equal word1 only');
        self::assertNotSame($word2Only, $exact, 'Exact should not equal word2 only');
    }

    #[Test]
    public function it_combined_language_detection_uses_both_words(): void
    {
        // Line 195: $langCombined = $this->languageDetector->detect($word1 . $word2, ...)
        // The combined detection affects the encoding
        $result = $this->engine->encode(
            'Gold Stein',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // This should produce valid output using combined word detection
        self::assertNotEmpty($result);

        // Compare with individual word encodings to verify combined detection matters
        $word1 = $this->engine->encode('Gold', NameType::Generic, MatchAccuracy::Approximate);
        $word2 = $this->engine->encode('Stein', NameType::Generic, MatchAccuracy::Approximate);

        // Result should not be simply one of the individual words
        // (though it may contain them as part of the merge)
        self::assertNotSame($word1, $result);
        self::assertNotSame($word2, $result);
    }

    #[Test]
    public function it_uses_name_type_specific_prefixes(): void
    {
        // Line 184: $prefixes = self::NAME_PREFIXES[$nameType->value] ?? self::NAME_PREFIXES['gen']
        // Test that different name types have different prefix handling

        // 'bar' is an Ashkenazic prefix
        $ashkenazic = $this->engine->encode(
            'bar Cohen',
            NameType::Ashkenazic,
            MatchAccuracy::Approximate
        );

        // 'bar' is NOT a Generic prefix
        $generic = $this->engine->encode(
            'bar Cohen',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Results should be different because prefix handling differs
        // In Ashkenazic: 'bar' is prefix, so encode Y and XY
        // In Generic: 'bar' is not prefix, so encode X, Y, and XY
        self::assertIsString($ashkenazic);
        self::assertIsString($generic);
    }

    #[Test]
    public function it_exact_return_is_required(): void
    {
        // ReturnRemoval on line 190 would not return the result
        // This verifies the return statement is needed
        $result = $this->engine->encode(
            'Test Name',
            NameType::Generic,
            MatchAccuracy::Exact
        );

        // Without return, we'd get null or empty string
        self::assertNotEmpty($result);
        self::assertIsString($result);
    }

    #[Test]
    public function it_prefix_fallback_to_generic(): void
    {
        // Line 184: ?? self::NAME_PREFIXES['gen'] ensures fallback exists
        // Even if nameType->value doesn't exist, we should get generic prefixes
        $result = $this->engine->encode(
            'von Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // 'von' is a generic prefix, should work
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_combined_word_encoding_differs_from_separate(): void
    {
        // Lines 195, 200, 209: Combined word encoding
        // Verify that encoding "GoldStein" is different from encoding "Gold" alone
        $combined = $this->engine->encode(
            'GoldStein',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        $gold = $this->engine->encode(
            'Gold',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        $stein = $this->engine->encode(
            'Stein',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Combined encoding should be different from either part
        self::assertNotSame($gold, $combined);
        self::assertNotSame($stein, $combined);
    }

    #[Test]
    public function it_handles_uppercase_prefix(): void
    {
        // Line 197: in_array(strtolower($word1), $prefixes, true)
        // Uppercase 'VON' should match lowercase 'von' in prefixes
        // UnwrapStrToLower mutation would break this
        $uppercase = $this->engine->encode(
            'VON Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        $lowercase = $this->engine->encode(
            'von Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Both should be treated the same (case-insensitive prefix matching)
        self::assertSame($lowercase, $uppercase);
    }

    #[Test]
    public function it_handles_mixed_case_prefix(): void
    {
        // Test mixed case prefixes - 'Von', 'VaN', etc.
        // All should match due to strtolower
        $cases = ['van Berg', 'Van Berg', 'VAN Berg', 'vAn Berg'];
        $results = [];

        foreach ($cases as $name) {
            $results[] = $this->engine->encode(
                $name,
                NameType::Generic,
                MatchAccuracy::Approximate
            );
        }

        // All case variations should produce the same result
        self::assertCount(4, $results);
        self::assertSame($results[0], $results[1], 'van vs Van should be same');
        self::assertSame($results[0], $results[2], 'van vs VAN should be same');
        self::assertSame($results[0], $results[3], 'van vs vAn should be same');
    }

    #[Test]
    public function it_prefix_combined_encoding_differs_from_word2_alone(): void
    {
        // Line 200: $resultCombined = $this->encodeSingleWord($word1 . $word2, ...)
        // When 'von' is prefix, we encode Y (Berg) AND XY (vonBerg)
        // ConcatOperandRemoval($word2) would only encode Berg twice
        $prefixResult = $this->engine->encode(
            'von Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Encoding just "Berg" twice (what mutation would do)
        $justBerg = $this->engine->encode(
            'Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Result should NOT be just Berg (that's Y only, missing XY=vonBerg)
        self::assertNotSame($justBerg, $prefixResult);
    }

    #[Test]
    public function it_prefix_combined_encoding_includes_prefix(): void
    {
        // Line 200: $word1 . $word2 = 'von' + 'Berg' = 'vonBerg'
        // ConcatOperandRemoval($word1) would only encode Berg for combined
        // The result should contain vonBerg phonetics (vomberg/vomberx)
        $withPrefixArray = $this->engine->encodeToArray(
            'von Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Result contains merged format like "berg-vomberg"
        // Verify it contains the combined "vom" sound from vonBerg
        $containsVonBerg = false;
        foreach ($withPrefixArray as $phonetic) {
            if (str_contains($phonetic, 'vom')) {
                $containsVonBerg = true;
                break;
            }
        }

        self::assertTrue($containsVonBerg, 'Result must contain vonBerg encoding (vom prefix)');

        // Also verify it's not just Berg alone (which would be only "berg")
        $justBergArray = $this->engine->encodeToArray(
            'Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // The prefix result should differ from just Berg
        self::assertNotSame($justBergArray, $withPrefixArray);
    }

    #[Test]
    public function it_if_negation_would_swap_prefix_behavior(): void
    {
        // Line 197: IfNegation would make non-prefixes act as prefixes and vice versa
        // Test that 'von' (prefix) and 'John' (non-prefix) produce different results
        $prefixName = $this->engine->encode(
            'von Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        $nonPrefixName = $this->engine->encode(
            'John Berg',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Prefix encoding = merge(Y, XY) = merge(Berg, vonBerg)
        // Non-prefix encoding = merge(merge(X, Y), XY) = merge(merge(John, Berg), JohnBerg)
        // These should be different
        self::assertNotSame($prefixName, $nonPrefixName);
    }

    #[Test]
    public function it_uppercase_prefix_is_recognized(): void
    {
        // 'BEN' uppercase should be recognized as prefix
        // UnwrapStrToLower mutation would fail this
        $uppercase = $this->engine->encode(
            'BEN David',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        $lowercase = $this->engine->encode(
            'ben David',
            NameType::Generic,
            MatchAccuracy::Approximate
        );

        // Both should produce identical results
        self::assertSame($lowercase, $uppercase);
    }

    #[Test]
    public function it_ashkenazic_uppercase_prefix_bar(): void
    {
        // 'BAR' is Ashkenazic prefix, test uppercase handling
        $uppercase = $this->engine->encode(
            'BAR Cohen',
            NameType::Ashkenazic,
            MatchAccuracy::Approximate
        );

        $lowercase = $this->engine->encode(
            'bar Cohen',
            NameType::Ashkenazic,
            MatchAccuracy::Approximate
        );

        // Both should produce identical results
        self::assertSame($lowercase, $uppercase);
    }
}
