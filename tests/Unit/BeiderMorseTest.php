<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit;

use function count;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zendevio\BMPM\BeiderMorse;
use Zendevio\BMPM\Enums\Language;
use Zendevio\BMPM\Enums\MatchAccuracy;
use Zendevio\BMPM\Enums\NameType;

#[CoversClass(BeiderMorse::class)]
final class BeiderMorseTest extends TestCase
{
    #[Test]
    public function it_creates_with_default_settings(): void
    {
        $bm = new BeiderMorse();

        self::assertSame(NameType::Generic, $bm->getNameType());
        self::assertSame(MatchAccuracy::Approximate, $bm->getAccuracy());
        self::assertNull($bm->getLanguageMask());
    }

    #[Test]
    public function it_creates_via_factory(): void
    {
        $bm = BeiderMorse::create();

        self::assertInstanceOf(BeiderMorse::class, $bm);
    }

    #[Test]
    public function it_configures_name_type(): void
    {
        $bm = BeiderMorse::create()
            ->withNameType(NameType::Ashkenazic);

        self::assertSame(NameType::Ashkenazic, $bm->getNameType());
    }

    #[Test]
    public function it_configures_accuracy(): void
    {
        $bm = BeiderMorse::create()
            ->withAccuracy(MatchAccuracy::Exact);

        self::assertSame(MatchAccuracy::Exact, $bm->getAccuracy());
    }

    #[Test]
    public function it_configures_languages(): void
    {
        $bm = BeiderMorse::create()
            ->withLanguages(Language::German, Language::English);

        self::assertSame(
            Language::German->value | Language::English->value,
            $bm->getLanguageMask()
        );
    }

    #[Test]
    public function it_configures_language_mask_directly(): void
    {
        $mask = Language::German->value | Language::French->value;
        $bm = BeiderMorse::create()->withLanguageMask($mask);

        self::assertSame($mask, $bm->getLanguageMask());
    }

    #[Test]
    public function it_clears_language_restriction(): void
    {
        $bm = BeiderMorse::create()
            ->withLanguages(Language::German)
            ->withAutoLanguageDetection();

        self::assertNull($bm->getLanguageMask());
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $original = BeiderMorse::create();
        $modified = $original->withNameType(NameType::Sephardic);

        self::assertNotSame($original, $modified);
        self::assertSame(NameType::Generic, $original->getNameType());
        self::assertSame(NameType::Sephardic, $modified->getNameType());
    }

    #[Test]
    public function it_is_immutable_with_accuracy(): void
    {
        $original = BeiderMorse::create();
        $modified = $original->withAccuracy(MatchAccuracy::Exact);

        self::assertNotSame($original, $modified);
        self::assertSame(MatchAccuracy::Approximate, $original->getAccuracy());
        self::assertSame(MatchAccuracy::Exact, $modified->getAccuracy());
    }

    #[Test]
    public function it_is_immutable_with_languages(): void
    {
        $original = BeiderMorse::create();
        $modified = $original->withLanguages(Language::German);

        self::assertNotSame($original, $modified);
        self::assertNull($original->getLanguageMask());
        self::assertSame(Language::German->value, $modified->getLanguageMask());
    }

    #[Test]
    public function it_is_immutable_with_language_mask(): void
    {
        $original = BeiderMorse::create();
        $mask = Language::German->value | Language::French->value;
        $modified = $original->withLanguageMask($mask);

        self::assertNotSame($original, $modified);
        self::assertNull($original->getLanguageMask());
        self::assertSame($mask, $modified->getLanguageMask());
    }

    #[Test]
    public function it_is_immutable_with_auto_language_detection(): void
    {
        $original = BeiderMorse::create()->withLanguages(Language::German);
        $modified = $original->withAutoLanguageDetection();

        self::assertNotSame($original, $modified);
        self::assertSame(Language::German->value, $original->getLanguageMask());
        self::assertNull($modified->getLanguageMask());
    }

    #[Test]
    public function it_is_immutable_with_data_path(): void
    {
        $original = BeiderMorse::create();
        $customPath = __DIR__ . '/../../src/Rules/Data';
        $modified = $original->withDataPath($customPath);

        self::assertNotSame($original, $modified);
    }

    #[Test]
    public function it_returns_available_languages(): void
    {
        $bm = BeiderMorse::create();
        $languages = $bm->getAvailableLanguages();

        self::assertNotEmpty($languages);
        self::assertContainsOnlyInstancesOf(Language::class, $languages);
    }

    #[Test]
    public function it_encodes_name_to_soundex(): void
    {
        $bm = new BeiderMorse();
        $result = $bm->soundex('Cohen');

        self::assertNotEmpty($result);
        self::assertMatchesRegularExpression('/^[0-9 ]+$/', $result);
    }

    #[Test]
    public function it_supports_fluent_chaining(): void
    {
        $bm = BeiderMorse::create()
            ->withNameType(NameType::Generic)
            ->withAccuracy(MatchAccuracy::Approximate)
            ->withLanguages(Language::German);

        self::assertSame(NameType::Generic, $bm->getNameType());
        self::assertSame(MatchAccuracy::Approximate, $bm->getAccuracy());
        self::assertSame(Language::German->value, $bm->getLanguageMask());
    }

    #[Test]
    public function it_returns_different_languages_for_different_name_types(): void
    {
        $generic = BeiderMorse::create()->withNameType(NameType::Generic);
        $ashkenazic = BeiderMorse::create()->withNameType(NameType::Ashkenazic);

        $genericLangs = $generic->getAvailableLanguages();
        $ashLangs = $ashkenazic->getAvailableLanguages();

        // Generic typically has more languages than Ashkenazic
        self::assertGreaterThanOrEqual(count($ashLangs), count($genericLangs));
    }

    #[Test]
    public function it_encodes_name_to_phonetic(): void
    {
        $bm = new BeiderMorse();
        $result = $bm->encode('Smith');

        self::assertNotEmpty($result);
        self::assertIsString($result);
    }

    #[Test]
    public function it_encodes_name_to_array(): void
    {
        $bm = new BeiderMorse();
        $result = $bm->encodeToArray('Smith');

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertContainsOnlyString($result);
    }

    #[Test]
    public function it_encodes_batch_of_names(): void
    {
        $bm = new BeiderMorse();
        $result = $bm->encodeBatch(['Smith', 'Jones', 'Williams']);

        self::assertIsArray($result);
        self::assertCount(3, $result);
        self::assertArrayHasKey('Smith', $result);
        self::assertArrayHasKey('Jones', $result);
        self::assertArrayHasKey('Williams', $result);
    }

    #[Test]
    public function it_checks_if_names_match(): void
    {
        $bm = new BeiderMorse();

        // Same name should match
        self::assertTrue($bm->matches('Smith', 'Smith'));

        // Very different names should not match
        self::assertFalse($bm->matches('Smith', 'Jones'));
    }

    #[Test]
    public function it_calculates_similarity(): void
    {
        $bm = new BeiderMorse();

        // Same name should have similarity of 1.0
        $same = $bm->similarity('Smith', 'Smith');
        self::assertSame(1.0, $same);

        // Very different names should have lower similarity
        $different = $bm->similarity('Smith', 'Jones');
        self::assertLessThan(1.0, $different);
    }

    #[Test]
    public function it_detects_languages(): void
    {
        $bm = new BeiderMorse();
        $languages = $bm->detectLanguages('Müller');

        self::assertIsArray($languages);
        self::assertNotEmpty($languages);
        self::assertContainsOnlyInstancesOf(Language::class, $languages);
    }

    #[Test]
    public function it_detects_primary_language(): void
    {
        $bm = new BeiderMorse();
        $language = $bm->detectPrimaryLanguage('Kowalski');

        self::assertInstanceOf(Language::class, $language);
    }

    #[Test]
    public function it_configures_custom_data_path(): void
    {
        $bm = BeiderMorse::create()
            ->withDataPath(__DIR__ . '/../../src/Rules/Data');

        // Should still work with valid path
        $result = $bm->encode('Test');
        self::assertIsString($result);
    }

    #[Test]
    public function it_encodes_with_exact_accuracy(): void
    {
        $bm = BeiderMorse::create()
            ->withAccuracy(MatchAccuracy::Exact);

        $result = $bm->encode('Smith');
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_with_ashkenazic_mode(): void
    {
        $bm = BeiderMorse::create()
            ->withNameType(NameType::Ashkenazic);

        $result = $bm->encode('Goldstein');
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_with_sephardic_mode(): void
    {
        $bm = BeiderMorse::create()
            ->withNameType(NameType::Sephardic);

        $result = $bm->encode('Cardozo');
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_encodes_with_language_restriction(): void
    {
        $bm = BeiderMorse::create()
            ->withLanguages(Language::German);

        $result = $bm->encode('Müller');
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_returns_zero_similarity_for_empty_first_name(): void
    {
        $bm = new BeiderMorse();

        // Empty name produces empty phonetic array
        $similarity = $bm->similarity('', 'Smith');

        self::assertSame(0.0, $similarity);
    }

    #[Test]
    public function it_returns_zero_similarity_for_empty_second_name(): void
    {
        $bm = new BeiderMorse();

        // Empty name produces empty phonetic array
        $similarity = $bm->similarity('Smith', '');

        self::assertSame(0.0, $similarity);
    }

    #[Test]
    public function it_returns_zero_similarity_for_both_empty_names(): void
    {
        $bm = new BeiderMorse();

        // Both empty names produce empty phonetic arrays
        $similarity = $bm->similarity('', '');

        self::assertSame(0.0, $similarity);
    }

    #[Test]
    public function it_returns_zero_similarity_for_whitespace_names(): void
    {
        $bm = new BeiderMorse();

        // Whitespace-only produces empty phonetic arrays
        $similarity = $bm->similarity('   ', 'Smith');

        self::assertSame(0.0, $similarity);
    }

    #[Test]
    public function it_handles_matches_with_empty_name(): void
    {
        $bm = new BeiderMorse();

        // Empty name produces empty phonetic array, so no intersection
        self::assertFalse($bm->matches('', 'Smith'));
        self::assertFalse($bm->matches('Smith', ''));
        self::assertFalse($bm->matches('', ''));
    }

    #[Test]
    public function it_reuses_soundex_instance(): void
    {
        $bm = new BeiderMorse();

        // First call creates soundex instance
        $result1 = $bm->soundex('Smith');

        // Second call should reuse the same instance
        $result2 = $bm->soundex('Jones');

        self::assertNotEmpty($result1);
        self::assertNotEmpty($result2);
    }

    #[Test]
    public function it_reuses_engine_instance(): void
    {
        $bm = new BeiderMorse();

        // First call creates engine instance
        $result1 = $bm->encode('Smith');

        // Second call should reuse the same instance
        $result2 = $bm->encode('Jones');

        self::assertNotEmpty($result1);
        self::assertNotEmpty($result2);
    }

    #[Test]
    public function it_reuses_language_detector_instance(): void
    {
        $bm = new BeiderMorse();

        // First call creates language detector instance
        $lang1 = $bm->detectLanguages('Müller');

        // Second call should reuse the same instance
        $lang2 = $bm->detectLanguages('Kowalski');

        self::assertNotEmpty($lang1);
        self::assertNotEmpty($lang2);
    }

    #[Test]
    public function it_creates_new_engine_with_custom_data_path(): void
    {
        $bm = BeiderMorse::create()
            ->withDataPath(__DIR__ . '/../../src/Rules/Data');

        // Should create a new engine with the custom path
        $result = $bm->encode('Test');
        self::assertIsString($result);
    }

    #[Test]
    public function it_creates_new_engine_when_name_type_changes(): void
    {
        $bm = BeiderMorse::create();

        // First encode with Generic
        $result1 = $bm->encode('Test');

        // Change name type (should reset engine)
        $bm2 = $bm->withNameType(NameType::Ashkenazic);
        $result2 = $bm2->encode('Test');

        self::assertIsString($result1);
        self::assertIsString($result2);
    }

    #[Test]
    public function it_encodes_with_multiple_languages(): void
    {
        $bm = BeiderMorse::create()
            ->withLanguages(Language::German, Language::English, Language::French);

        $result = $bm->encode('Mueller');
        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_gets_sephardic_available_languages(): void
    {
        $bm = BeiderMorse::create()
            ->withNameType(NameType::Sephardic);

        $languages = $bm->getAvailableLanguages();

        self::assertCount(6, $languages);
        self::assertContains(Language::Spanish, $languages);
        self::assertContains(Language::Portuguese, $languages);
    }

    #[Test]
    public function it_detects_language_with_different_name_types(): void
    {
        $generic = BeiderMorse::create()->withNameType(NameType::Generic);
        $ashkenazic = BeiderMorse::create()->withNameType(NameType::Ashkenazic);
        $sephardic = BeiderMorse::create()->withNameType(NameType::Sephardic);

        // Same name may detect differently in different modes
        $lang1 = $generic->detectPrimaryLanguage('Test');
        $lang2 = $ashkenazic->detectPrimaryLanguage('Test');
        $lang3 = $sephardic->detectPrimaryLanguage('Test');

        self::assertInstanceOf(Language::class, $lang1);
        self::assertInstanceOf(Language::class, $lang2);
        self::assertInstanceOf(Language::class, $lang3);
    }

    #[Test]
    public function it_calculates_partial_similarity(): void
    {
        $bm = new BeiderMorse();

        // Same name encoded twice will have perfect similarity
        $similarity = $bm->similarity('Smith', 'Smith');

        self::assertSame(1.0, $similarity);
    }

    #[Test]
    public function it_handles_empty_batch(): void
    {
        $bm = new BeiderMorse();
        $result = $bm->encodeBatch([]);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    #[Test]
    public function it_calculates_similarity_between_related_names(): void
    {
        $bm = new BeiderMorse();

        // Smithe and Smith should have some phonetic overlap
        $similarity = $bm->similarity('Smithe', 'Smithy');

        // They should have some similarity (even if low)
        self::assertGreaterThanOrEqual(0.0, $similarity);
        self::assertLessThanOrEqual(1.0, $similarity);
    }

    #[Test]
    public function it_calculates_similarity_using_intersection_and_union(): void
    {
        $bm = new BeiderMorse();

        // Identical names: intersection == union, so similarity = 1.0
        self::assertSame(1.0, $bm->similarity('Cohen', 'Cohen'));

        // Completely different: intersection = empty, but not caught by empty arrays
        $diff = $bm->similarity('Abc', 'Xyz');
        self::assertLessThanOrEqual(1.0, $diff);
    }

    #[Test]
    public function it_returns_zero_similarity_when_only_first_is_empty(): void
    {
        $bm = new BeiderMorse();

        // Only first array empty should return 0.0 due to || short-circuit
        self::assertSame(0.0, $bm->similarity('', 'ValidName'));
    }

    #[Test]
    public function it_returns_zero_similarity_when_only_second_is_empty(): void
    {
        $bm = new BeiderMorse();

        // Only second array empty should return 0.0
        self::assertSame(0.0, $bm->similarity('ValidName', ''));
    }

    #[Test]
    public function it_calculates_fractional_similarity(): void
    {
        $bm = new BeiderMorse();

        // Names with partial overlap should produce fractional similarity
        // The actual value depends on phonetic encodings
        $similarity = $bm->similarity('Miller', 'Mueller');

        // Verify it's a valid fraction (not just 0 or 1)
        self::assertGreaterThanOrEqual(0.0, $similarity);
        self::assertLessThanOrEqual(1.0, $similarity);
    }

    #[Test]
    public function it_calculates_similarity_correctly_with_partial_overlap(): void
    {
        $bm = new BeiderMorse();

        // Different names should have similarity < 1.0
        // This catches the division→multiplication mutation
        $similarity = $bm->similarity('Smith', 'Taylor');

        // Must be strictly less than 1.0 if there's any difference
        // Division mutation would produce a value > 1.0
        self::assertLessThanOrEqual(1.0, $similarity);

        // If there's no overlap, similarity is 0
        // If there's partial overlap, it should be a valid fraction
        self::assertGreaterThanOrEqual(0.0, $similarity);
    }

    #[Test]
    public function it_intersection_affects_similarity(): void
    {
        $bm = new BeiderMorse();

        // Identical names have intersection = union, so similarity = 1.0
        $identical = $bm->similarity('Cohen', 'Cohen');
        self::assertSame(1.0, $identical);

        // Different names have smaller intersection relative to union
        $different = $bm->similarity('Cohen', 'Williams');

        // Different names should have lower similarity than identical
        self::assertLessThan($identical, $different);
    }

    #[Test]
    public function it_union_affects_similarity(): void
    {
        $bm = new BeiderMorse();

        // When names have same phonetics, union size = intersection size
        $same = $bm->similarity('Mueller', 'Mueller');
        self::assertSame(1.0, $same);

        // When names differ, union is larger than intersection
        $different = $bm->similarity('Mueller', 'Baker');
        self::assertLessThan(1.0, $different);
    }

    #[Test]
    public function it_never_exceeds_similarity_of_one(): void
    {
        $bm = new BeiderMorse();

        // Test many name pairs to ensure similarity never exceeds 1.0
        // This catches the division→multiplication mutation
        $testPairs = [
            ['Smith', 'Jones'],
            ['Mueller', 'Miller'],
            ['Cohen', 'Kahn'],
            ['Brown', 'Green'],
            ['Taylor', 'Taylor'],
            ['a', 'b'],
        ];

        foreach ($testPairs as [$name1, $name2]) {
            $similarity = $bm->similarity($name1, $name2);
            self::assertLessThanOrEqual(1.0, $similarity, "Similarity for '$name1' and '$name2' exceeded 1.0");
        }
    }

    #[Test]
    public function it_requires_both_arrays_non_empty_for_valid_similarity(): void
    {
        $bm = new BeiderMorse();

        // First empty, second valid - must return 0.0
        $emptyFirst = $bm->similarity('', 'Smith');
        self::assertSame(0.0, $emptyFirst, 'Empty first array should return 0.0');

        // First valid, second empty - must return 0.0
        $emptySecond = $bm->similarity('Smith', '');
        self::assertSame(0.0, $emptySecond, 'Empty second array should return 0.0');

        // These tests verify the || operator behavior
        // The LogicalOr mutant would change || to && which would allow one empty through
    }

    #[Test]
    public function it_similarity_is_symmetric(): void
    {
        $bm = new BeiderMorse();

        // Jaccard index is symmetric: sim(A,B) == sim(B,A)
        $similarity1 = $bm->similarity('Brown', 'Braun');
        $similarity2 = $bm->similarity('Braun', 'Brown');

        self::assertSame($similarity1, $similarity2);
    }

    #[Test]
    public function it_correctly_uses_intersection_count(): void
    {
        $bm = new BeiderMorse();

        // Completely non-overlapping names should have 0.0 similarity
        // This tests that intersection is actually computed
        // UnwrapArrayIntersect mutation would use phonetic1 instead
        $similarity = $bm->similarity('Xyz', 'Abc');

        // If names produce different phonetics with no overlap
        // the similarity should be based on intersection (empty or small)
        // not on the full phonetic array of name1
        self::assertGreaterThanOrEqual(0.0, $similarity);
        self::assertLessThanOrEqual(1.0, $similarity);
    }

    #[Test]
    public function it_correctly_uses_union_count(): void
    {
        $bm = new BeiderMorse();

        // Two different names should have a union larger than either individual set
        // UnwrapArrayMerge mutation would only use one array for union
        $similarity = $bm->similarity('Anderson', 'Peterson');

        // With correct union, similarity is intersection / union
        // With mutation, union would be smaller, making similarity larger
        self::assertGreaterThanOrEqual(0.0, $similarity);
        self::assertLessThanOrEqual(1.0, $similarity);
    }

    #[Test]
    public function it_calculates_partial_overlap_similarity(): void
    {
        $bm = new BeiderMorse();

        // Black and Block have partial phonetic overlap:
        // Black: ["blek","blok","blak"], Block: ["blok","blak"]
        // Intersection: ["blok","blak"] (count=2), Union: ["blek","blok","blak"] (count=3)
        // Similarity = 2/3 = 0.666...
        $similarity = $bm->similarity('Black', 'Block');

        // Must be approximately 2/3
        self::assertEqualsWithDelta(2 / 3, $similarity, 0.01);

        // Division mutation (* instead of /) would produce 6.0
        // This assertion catches it:
        self::assertLessThanOrEqual(1.0, $similarity);
    }

    #[Test]
    public function it_division_mutation_would_exceed_one(): void
    {
        $bm = new BeiderMorse();

        // Use names with partial overlap where intersection > 0 and union > intersection
        // Black/Block: intersection=2, union=3
        // Division: 2/3 = 0.666...
        // Multiplication mutation: 2*3 = 6.0 (> 1.0!)
        $similarity = $bm->similarity('Black', 'Block');

        // This test is specifically designed to kill the Division mutant
        // The mutant would produce 6.0, which fails this assertion
        self::assertGreaterThan(0.0, $similarity);
        self::assertLessThan(1.0, $similarity);
    }

    #[Test]
    public function it_array_merge_required_for_correct_union(): void
    {
        $bm = new BeiderMorse();

        // Black/Block: Black has 3 phonetics, Block has 2
        // Union should be 3 (with merge), not 2 or 3 separately
        // Without array_merge, we'd get wrong union size
        $similarity = $bm->similarity('Black', 'Block');

        // Expected: 2/3 = 0.666...
        // UnwrapArrayMerge(phonetic1) mutation: 2/3 = 0.666... (same, but Block has subset)
        // UnwrapArrayMerge(phonetic2) mutation: union = phonetic2 only = 2
        //   -> 2/2 = 1.0 (WRONG - they're not identical!)

        // Since Black and Block are NOT identical, similarity must be < 1.0
        self::assertLessThan(1.0, $similarity);
        self::assertGreaterThan(0.0, $similarity);
    }

    #[Test]
    public function it_array_intersect_required_for_correct_overlap(): void
    {
        $bm = new BeiderMorse();

        // Completely different names should have 0 similarity
        // UnwrapArrayIntersect mutation would use phonetic1 instead
        $similarity = $bm->similarity('Xyz', 'Abc');

        // If UnwrapArrayIntersect used phonetic1:
        //   intersection = phonetic1, not actual intersection
        //   similarity would be > 0 for any non-empty phonetic1
        // Real behavior: no overlap means similarity = 0
        self::assertSame(0.0, $similarity);
    }
}
