<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zendevio\BMPM\Enums\Language;
use Zendevio\BMPM\Enums\NameType;

#[CoversClass(Language::class)]
final class LanguageTest extends TestCase
{
    #[Test]
    public function it_has_correct_bitmask_values(): void
    {
        self::assertSame(1, Language::Any->value);
        self::assertSame(2, Language::Arabic->value);
        self::assertSame(4, Language::Cyrillic->value);
        self::assertSame(128, Language::German->value);
        self::assertSame(32, Language::English->value);
    }

    #[Test]
    public function it_returns_rule_names(): void
    {
        self::assertSame('any', Language::Any->ruleName());
        self::assertSame('german', Language::German->ruleName());
        self::assertSame('greeklatin', Language::GreekLatin->ruleName());
    }

    #[Test]
    public function it_returns_labels(): void
    {
        self::assertSame('Any', Language::Any->label());
        self::assertSame('German', Language::German->label());
        self::assertSame('Greek (Latin script)', Language::GreekLatin->label());
    }

    #[Test]
    public function it_creates_from_string(): void
    {
        self::assertSame(Language::German, Language::fromString('german'));
        self::assertSame(Language::German, Language::fromString('GERMAN'));
        self::assertSame(Language::GreekLatin, Language::fromString('greeklatin'));
        self::assertNull(Language::fromString('invalid'));
    }

    #[Test]
    public function it_combines_bitmask(): void
    {
        $mask = Language::combineMask([Language::German, Language::English]);

        self::assertSame(160, $mask); // 128 + 32
    }

    #[Test]
    public function it_extracts_from_mask(): void
    {
        $languages = Language::fromMask(160); // German + English

        self::assertContains(Language::German, $languages);
        self::assertContains(Language::English, $languages);
        self::assertNotContains(Language::French, $languages);
    }

    #[Test]
    public function it_checks_mask_membership(): void
    {
        $mask = 160; // German + English

        self::assertTrue(Language::German->isInMask($mask));
        self::assertTrue(Language::English->isInMask($mask));
        self::assertFalse(Language::French->isInMask($mask));
    }

    #[Test]
    public function it_returns_languages_for_generic_name_type(): void
    {
        $languages = Language::forNameType(NameType::Generic);

        self::assertCount(20, $languages);
        self::assertContains(Language::Arabic, $languages);
        self::assertContains(Language::Turkish, $languages);
    }

    #[Test]
    public function it_returns_languages_for_ashkenazic_name_type(): void
    {
        $languages = Language::forNameType(NameType::Ashkenazic);

        self::assertCount(11, $languages);
        self::assertContains(Language::German, $languages);
        self::assertContains(Language::Polish, $languages);
        self::assertNotContains(Language::Arabic, $languages);
    }

    #[Test]
    public function it_returns_languages_for_sephardic_name_type(): void
    {
        $languages = Language::forNameType(NameType::Sephardic);

        self::assertCount(6, $languages);
        self::assertContains(Language::Spanish, $languages);
        self::assertContains(Language::Portuguese, $languages);
        self::assertNotContains(Language::German, $languages);
    }

    #[Test]
    public function it_calculates_correct_index(): void
    {
        self::assertSame(0, Language::Any->index());
        self::assertSame(5, Language::English->index());
    }

    #[Test]
    public function it_creates_from_index(): void
    {
        self::assertSame(Language::Any, Language::fromIndex(0));
        self::assertSame(Language::English, Language::fromIndex(5));
        self::assertNull(Language::fromIndex(100));
    }

    #[Test]
    public function it_creates_from_index_with_ashkenazic(): void
    {
        self::assertSame(Language::Any, Language::fromIndex(0, NameType::Ashkenazic));
        self::assertSame(Language::German, Language::fromIndex(4, NameType::Ashkenazic));
    }

    #[Test]
    public function it_creates_from_index_with_sephardic(): void
    {
        self::assertSame(Language::Any, Language::fromIndex(0, NameType::Sephardic));
        self::assertSame(Language::Spanish, Language::fromIndex(5, NameType::Sephardic));
    }

    #[Test]
    public function it_returns_zero_for_language_not_in_name_type(): void
    {
        // Arabic is not in Sephardic, so index should return 0 (default)
        self::assertSame(0, Language::Arabic->index(NameType::Sephardic));

        // Turkish is not in Ashkenazic, so index should return 0 (default)
        self::assertSame(0, Language::Turkish->index(NameType::Ashkenazic));
    }

    #[Test]
    public function it_returns_correct_index_for_language_in_name_type(): void
    {
        // Spanish is at index 5 in Sephardic
        self::assertSame(5, Language::Spanish->index(NameType::Sephardic));

        // German is at index 4 in Ashkenazic
        self::assertSame(4, Language::German->index(NameType::Ashkenazic));
    }

    #[Test]
    public function it_returns_all_rule_names(): void
    {
        // Test all rule names are lowercase
        foreach (Language::cases() as $language) {
            $ruleName = $language->ruleName();
            self::assertSame(strtolower($ruleName), $ruleName);
            self::assertNotEmpty($ruleName);
        }
    }

    #[Test]
    public function it_returns_all_labels(): void
    {
        // Test all labels are non-empty and properly formatted
        foreach (Language::cases() as $language) {
            $label = $language->label();
            self::assertNotEmpty($label);
            // First character should be uppercase
            self::assertSame(strtoupper($label[0]), $label[0]);
        }
    }

    #[Test]
    public function it_combines_empty_mask(): void
    {
        $mask = Language::combineMask([]);

        self::assertSame(0, $mask);
    }

    #[Test]
    public function it_extracts_from_empty_mask(): void
    {
        $languages = Language::fromMask(0);

        self::assertSame([], $languages);
    }

    #[Test]
    public function it_extracts_from_any_mask(): void
    {
        $languages = Language::fromMask(1);

        self::assertCount(1, $languages);
        self::assertContains(Language::Any, $languages);
    }

    #[Test]
    public function it_checks_mask_membership_with_zero(): void
    {
        self::assertFalse(Language::German->isInMask(0));
    }

    #[Test]
    public function it_creates_from_string_trimmed(): void
    {
        self::assertSame(Language::German, Language::fromString('  german  '));
        self::assertSame(Language::English, Language::fromString('  ENGLISH  '));
    }
}
