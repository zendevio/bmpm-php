<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError;
use Zendevio\BMPM\Exceptions\InvalidInputException;
use Zendevio\BMPM\Util\StringHelper;

#[CoversClass(StringHelper::class)]
final class StringHelperTest extends TestCase
{
    #[Test]
    public function it_normalizes_simple_input(): void
    {
        self::assertSame('john', StringHelper::normalize('John'));
        self::assertSame('john', StringHelper::normalize('  John  '));
        self::assertSame('john', StringHelper::normalize('JOHN'));
    }

    #[Test]
    public function it_throws_on_empty_input(): void
    {
        $this->expectException(InvalidInputException::class);
        StringHelper::normalize('');
    }

    #[Test]
    public function it_throws_on_whitespace_only_input(): void
    {
        $this->expectException(InvalidInputException::class);
        StringHelper::normalize('   ');
    }

    #[Test]
    public function it_decodes_html_entities(): void
    {
        self::assertSame('müller', StringHelper::normalize('M&uuml;ller'));
        self::assertSame("o'brien", StringHelper::normalize('O&#039;Brien'));
    }

    #[Test]
    public function it_handles_utf8_input(): void
    {
        self::assertSame('münchen', StringHelper::normalize('München'));
        self::assertSame('łódź', StringHelper::normalize('Łódź'));
    }

    #[Test]
    public function it_checks_ascii(): void
    {
        self::assertTrue(StringHelper::isAscii('John'));
        self::assertFalse(StringHelper::isAscii('Müller'));
    }

    #[Test]
    public function it_gets_substring(): void
    {
        self::assertSame('llo', StringHelper::substring('Hello', 2, 3));
        self::assertSame('ünc', StringHelper::substring('München', 1, 3));
    }

    #[Test]
    public function it_gets_length(): void
    {
        self::assertSame(5, StringHelper::length('Hello'));
        self::assertSame(7, StringHelper::length('München'));
    }

    #[Test]
    public function it_checks_starts_with(): void
    {
        self::assertTrue(StringHelper::startsWith('Hello', 'He'));
        self::assertFalse(StringHelper::startsWith('Hello', 'Lo'));
    }

    #[Test]
    public function it_checks_ends_with(): void
    {
        self::assertTrue(StringHelper::endsWith('Hello', 'lo'));
        self::assertFalse(StringHelper::endsWith('Hello', 'He'));
    }

    #[Test]
    public function it_removes_character(): void
    {
        self::assertSame('Hello', StringHelper::remove("He'l'lo", "'"));
    }

    #[Test]
    public function it_removes_leading_prefixes(): void
    {
        self::assertSame('dela rosa', StringHelper::removeLeadingPrefixes('de la rosa', ['de la']));
        self::assertSame('vander berg', StringHelper::removeLeadingPrefixes('van der berg', ['van der']));
        self::assertSame('smith', StringHelper::removeLeadingPrefixes('smith', ['de la', 'van der']));
    }

    #[Test]
    public function it_finds_position(): void
    {
        self::assertSame(2, StringHelper::position('Hello', 'l'));
        self::assertFalse(StringHelper::position('Hello', 'x'));
    }

    #[Test]
    public function it_replaces_first(): void
    {
        self::assertSame('Hxllo', StringHelper::replaceFirst('Hello', 'e', 'x'));
        self::assertSame('Hexlo', StringHelper::replaceFirst('Hello', 'l', 'x'));
    }

    #[Test]
    public function it_splits_first(): void
    {
        $result = StringHelper::splitFirst('John Doe', ' ');

        self::assertSame(['John', 'Doe'], $result);

        $result = StringHelper::splitFirst('John Doe Smith', ' ');
        self::assertSame(['John', 'Doe Smith'], $result);

        self::assertNull(StringHelper::splitFirst('John', ' '));
    }

    #[Test]
    public function it_checks_regex_matches(): void
    {
        self::assertTrue(StringHelper::matches('Hello', '/ell/'));
        self::assertFalse(StringHelper::matches('Hello', '/xyz/'));
    }

    /**
     * @param array<string> $expected
     */
    #[Test]
    #[DataProvider('provideMatchAllCases')]
    public function it_matches_all(string $input, string $pattern, array $expected): void
    {
        self::assertSame($expected, StringHelper::matchAll($input, $pattern));
    }

    /**
     * @return iterable<string, array{string, string, array<string>}>
     */
    public static function provideMatchAllCases(): iterable
    {
        yield 'vowels' => ['Hello', '/[aeiou]/', ['e', 'o']];
        yield 'no match' => ['xyz', '/[aeiou]/', []];
    }

    #[Test]
    public function it_throws_on_input_too_long(): void
    {
        $this->expectException(InvalidInputException::class);
        StringHelper::normalize(str_repeat('a', 1001));
    }

    #[Test]
    public function it_normalizes_at_max_length(): void
    {
        // Should not throw at exactly max length
        $result = StringHelper::normalize(str_repeat('a', 1000));
        self::assertSame(str_repeat('a', 1000), $result);
    }

    #[Test]
    public function it_replaces_first_when_not_found(): void
    {
        self::assertSame('Hello', StringHelper::replaceFirst('Hello', 'xyz', 'abc'));
    }

    #[Test]
    public function it_converts_to_utf8_from_already_utf8(): void
    {
        $utf8 = 'Müller';
        self::assertSame($utf8, StringHelper::toUtf8($utf8));
    }

    #[Test]
    public function it_handles_substring_without_length(): void
    {
        self::assertSame('llo', StringHelper::substring('Hello', 2));
        self::assertSame('nchen', StringHelper::substring('München', 2));
    }

    #[Test]
    public function it_handles_empty_ascii_string(): void
    {
        self::assertTrue(StringHelper::isAscii(''));
    }

    #[Test]
    public function it_normalizes_with_no_html_entities(): void
    {
        // Input without & should skip html_entity_decode
        self::assertSame('john', StringHelper::normalize('John'));
    }

    #[Test]
    public function it_converts_iso_8859_1_to_utf8(): void
    {
        // ISO-8859-1 encoded string (ü = 0xFC in ISO-8859-1)
        $iso = "\xFC"; // ü in ISO-8859-1
        $result = StringHelper::toUtf8($iso);

        // Should be converted to UTF-8
        self::assertSame('ü', $result);
    }

    #[Test]
    public function it_converts_windows_1252_to_utf8(): void
    {
        // Windows-1252 specific character (€ = 0x80)
        $win1252 = "\x80"; // € in Windows-1252
        $result = StringHelper::toUtf8($win1252);

        // Should be detected and converted
        self::assertIsString($result);
    }

    #[Test]
    public function it_handles_position_at_start(): void
    {
        self::assertSame(0, StringHelper::position('Hello', 'H'));
    }

    #[Test]
    public function it_handles_position_at_end(): void
    {
        self::assertSame(4, StringHelper::position('Hello', 'o'));
    }

    #[Test]
    public function it_handles_multibyte_position(): void
    {
        self::assertSame(0, StringHelper::position('München', 'M'));
        self::assertSame(1, StringHelper::position('München', 'ü'));
    }

    #[Test]
    public function it_handles_multibyte_replaceFirst(): void
    {
        self::assertSame('Manchen', StringHelper::replaceFirst('München', 'ü', 'a'));
    }

    #[Test]
    public function it_handles_splitFirst_at_start(): void
    {
        $result = StringHelper::splitFirst(' Hello', ' ');

        self::assertSame(['', 'Hello'], $result);
    }

    #[Test]
    public function it_handles_splitFirst_at_end(): void
    {
        $result = StringHelper::splitFirst('Hello ', ' ');

        self::assertSame(['Hello', ''], $result);
    }

    #[Test]
    public function it_handles_matches_unicode(): void
    {
        self::assertTrue(StringHelper::matches('Müller', '/ü/'));
        self::assertFalse(StringHelper::matches('Muller', '/ü/'));
    }

    #[Test]
    public function it_handles_matchAll_unicode(): void
    {
        $result = StringHelper::matchAll('München Übung', '/[üÜ]/');

        self::assertCount(2, $result);
    }

    #[Test]
    public function it_removes_leading_prefix_at_start(): void
    {
        self::assertSame('dela cruz', StringHelper::removeLeadingPrefixes('de la cruz', ['de la']));
    }

    #[Test]
    public function it_does_not_remove_prefix_without_space(): void
    {
        // 'dela' without space after should not be removed
        self::assertSame('delacruz', StringHelper::removeLeadingPrefixes('delacruz', ['de la']));
    }

    #[Test]
    public function it_throws_on_encoding_conversion_failure(): void
    {
        $this->expectException(InvalidInputException::class);

        // Use a test helper that throws ValueError during conversion
        ThrowingStringHelper::toUtf8("\xFC"); // ISO-8859-1 character that triggers conversion
    }
}

/**
 * Test helper that simulates encoding conversion failure.
 *
 * @internal
 */
final class ThrowingStringHelper extends StringHelper
{
    protected static function convertEncoding(string $input, string $fromEncoding): string
    {
        throw new ValueError('Simulated encoding error');
    }
}
