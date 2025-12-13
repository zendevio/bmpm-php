<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zendevio\BMPM\Util\PhoneticExpander;

#[CoversClass(PhoneticExpander::class)]
final class PhoneticExpanderTest extends TestCase
{
    #[Test]
    public function it_expands_simple_string(): void
    {
        $result = PhoneticExpander::expand('abc');

        self::assertSame(['abc'], $result);
    }

    #[Test]
    public function it_expands_alternatives(): void
    {
        $result = PhoneticExpander::expand('(a|b)');

        self::assertCount(2, $result);
        self::assertContains('a', $result);
        self::assertContains('b', $result);
    }

    #[Test]
    public function it_expands_prefix_alternatives(): void
    {
        $result = PhoneticExpander::expand('(a|b)c');

        self::assertCount(2, $result);
        self::assertContains('ac', $result);
        self::assertContains('bc', $result);
    }

    #[Test]
    public function it_expands_suffix_alternatives(): void
    {
        $result = PhoneticExpander::expand('a(b|c)');

        self::assertCount(2, $result);
        self::assertContains('ab', $result);
        self::assertContains('ac', $result);
    }

    #[Test]
    public function it_expands_multiple_alternative_groups(): void
    {
        $result = PhoneticExpander::expand('(a|b)(c|d)');

        self::assertCount(4, $result);
        self::assertContains('ac', $result);
        self::assertContains('ad', $result);
        self::assertContains('bc', $result);
        self::assertContains('bd', $result);
    }

    #[Test]
    public function it_removes_duplicates(): void
    {
        $result = PhoneticExpander::expand('(a|a)');

        self::assertSame(['a'], $result);
    }

    #[Test]
    public function it_collapses_array_to_parenthesized(): void
    {
        self::assertSame('(a|b|c)', PhoneticExpander::collapse(['a', 'b', 'c']));
        self::assertSame('a', PhoneticExpander::collapse(['a']));
        self::assertSame('', PhoneticExpander::collapse([]));
    }

    #[Test]
    public function it_removes_duplicates_from_string(): void
    {
        self::assertSame('a|b|c', PhoneticExpander::removeDuplicates('a|b|a|c|b'));
    }

    #[Test]
    public function it_normalizes_language_attributes(): void
    {
        $result = PhoneticExpander::normalizeLanguageAttributes('abc[128]def[32]', false);

        // Attributes are ANDed together
        self::assertSame('abcdef[0]', $result);
    }

    #[Test]
    public function it_strips_language_attributes(): void
    {
        $result = PhoneticExpander::normalizeLanguageAttributes('abc[128]def[32]', true);

        self::assertSame('abcdef', $result);
    }

    #[Test]
    public function it_detects_alternates(): void
    {
        self::assertTrue(PhoneticExpander::hasAlternates('(a|b)'));
        self::assertTrue(PhoneticExpander::hasAlternates('a|b'));
        self::assertFalse(PhoneticExpander::hasAlternates('abc'));
    }

    #[Test]
    public function it_counts_alternatives(): void
    {
        self::assertSame(1, PhoneticExpander::countAlternatives('abc'));
        self::assertSame(2, PhoneticExpander::countAlternatives('(a|b)'));
        self::assertSame(4, PhoneticExpander::countAlternatives('(a|b)(c|d)'));
    }

    #[Test]
    public function it_merges_phonetics(): void
    {
        self::assertSame('a-b', PhoneticExpander::merge('a', 'b'));
        self::assertSame('b', PhoneticExpander::merge('', 'b'));
        self::assertSame('a', PhoneticExpander::merge('a', ''));
    }

    #[Test]
    public function it_detects_language_attributes(): void
    {
        self::assertTrue(PhoneticExpander::hasLanguageAttributes('abc[128]'));
        self::assertFalse(PhoneticExpander::hasLanguageAttributes('abc'));
    }

    #[Test]
    public function it_strips_language_attributes_convenience(): void
    {
        self::assertSame('abcdef', PhoneticExpander::stripLanguageAttributes('abc[128]def'));
    }

    #[Test]
    public function it_handles_malformed_unclosed_parenthesis(): void
    {
        // Malformed input with unclosed parenthesis should return as-is
        $result = PhoneticExpander::expand('(abc');

        self::assertSame(['(abc'], $result);
    }

    #[Test]
    public function it_handles_malformed_unclosed_bracket(): void
    {
        // Malformed input with unclosed bracket should return as-is
        $result = PhoneticExpander::normalizeLanguageAttributes('abc[128', false);

        self::assertSame('abc[128', $result);
    }

    #[Test]
    public function it_handles_empty_alternatives(): void
    {
        // Alternative with empty option
        $result = PhoneticExpander::expand('(a|)');

        // Empty values are filtered out
        self::assertSame(['a'], $result);
    }

    #[Test]
    public function it_handles_deeply_nested_alternatives(): void
    {
        $result = PhoneticExpander::expand('(a|b)(c|d)(e|f)');

        self::assertCount(8, $result);
    }

    #[Test]
    public function it_collapses_removes_empty_values(): void
    {
        self::assertSame('(a|b)', PhoneticExpander::collapse(['a', '', 'b', '']));
    }

    #[Test]
    public function it_collapses_removes_duplicates(): void
    {
        self::assertSame('(a|b)', PhoneticExpander::collapse(['a', 'b', 'a', 'b']));
    }

    #[Test]
    public function it_removes_duplicates_without_pipes(): void
    {
        // String without pipes should return as-is
        self::assertSame('abc', PhoneticExpander::removeDuplicates('abc'));
    }

    #[Test]
    public function it_normalizes_non_numeric_attributes(): void
    {
        // Non-numeric attribute value should be ignored
        $result = PhoneticExpander::normalizeLanguageAttributes('abc[xyz]def', false);

        self::assertSame('abcdef', $result);
    }

    #[Test]
    public function it_normalizes_multiple_numeric_attributes(): void
    {
        // Multiple numeric attributes should be ANDed together
        // 128 & 160 = 128 (both have German bit set)
        $result = PhoneticExpander::normalizeLanguageAttributes('abc[128]def[160]', false);

        self::assertSame('abcdef[128]', $result);
    }

    #[Test]
    public function it_merge_with_custom_separator(): void
    {
        self::assertSame('a_b', PhoneticExpander::merge('a', 'b', '_'));
    }

    #[Test]
    public function it_expands_filters_zero_attribute(): void
    {
        // [0] attributes should be normalized but value preserved
        $result = PhoneticExpander::expand('a[0]');

        // The expand function normalizes language attributes
        // 'a[0]' expands to 'a[0]' which gets filtered as '[0]' is filtered
        self::assertCount(1, $result);
        self::assertStringStartsWith('a', $result[0]);
    }

    #[Test]
    public function it_handles_only_parentheses(): void
    {
        $result = PhoneticExpander::expand('()');

        // Empty parentheses produce empty string which is filtered
        self::assertSame([], $result);
    }

    #[Test]
    public function it_handles_single_pipe(): void
    {
        $result = PhoneticExpander::expand('(|)');

        // Both alternatives are empty, filtered out
        self::assertSame([], $result);
    }
}
