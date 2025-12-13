<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zendevio\BMPM\Rules\Rule;

#[CoversClass(Rule::class)]
final class RuleTest extends TestCase
{
    #[Test]
    public function it_creates_from_constructor(): void
    {
        $rule = new Rule(
            pattern: 'sch',
            leftContext: '[aeiou]',
            rightContext: '[ei]',
            phonetic: 'S',
            languageMask: 128,
            logicalOp: Rule::LOGICAL_ANY,
        );

        self::assertSame('sch', $rule->pattern);
        self::assertSame('[aeiou]', $rule->leftContext);
        self::assertSame('[ei]', $rule->rightContext);
        self::assertSame('S', $rule->phonetic);
        self::assertSame(128, $rule->languageMask);
        self::assertSame(Rule::LOGICAL_ANY, $rule->logicalOp);
    }

    #[Test]
    public function it_creates_from_array(): void
    {
        $rule = Rule::fromArray(['sch', '[aeiou]', '[ei]', 'S', 128, 'ALL']);

        self::assertSame('sch', $rule->pattern);
        self::assertSame('[aeiou]', $rule->leftContext);
        self::assertSame('[ei]', $rule->rightContext);
        self::assertSame('S', $rule->phonetic);
        self::assertSame(128, $rule->languageMask);
        self::assertSame('ALL', $rule->logicalOp);
    }

    #[Test]
    public function it_creates_from_json(): void
    {
        $rule = Rule::fromJson([
            'pattern' => 'sch',
            'leftContext' => '[aeiou]',
            'rightContext' => '[ei]',
            'phonetic' => 'S',
            'languageMask' => 128,
            'logicalOp' => 'ALL',
        ]);

        self::assertSame('sch', $rule->pattern);
        self::assertSame('S', $rule->phonetic);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $rule = new Rule('sch', '', '', 'S', 128, 'ANY');
        $array = $rule->toArray();

        self::assertSame(['sch', '', '', 'S', 128, 'ANY'], $array);
    }

    #[Test]
    public function it_converts_to_json(): void
    {
        $rule = new Rule('sch', '[aeiou]', '', 'S');
        $json = $rule->toJson();

        self::assertSame('sch', $json['pattern']);
        self::assertSame('S', $json['phonetic']);
        self::assertSame('[aeiou]', $json['leftContext']);
        self::assertArrayNotHasKey('rightContext', $json);
    }

    #[Test]
    public function it_checks_context(): void
    {
        $rule1 = new Rule('a', '', '', 'b');
        $rule2 = new Rule('a', '[x]', '', 'b');

        self::assertFalse($rule1->hasContext());
        self::assertTrue($rule2->hasContext());
    }

    #[Test]
    public function it_checks_language_restriction(): void
    {
        $rule1 = new Rule('a', '', '', 'b');
        $rule2 = new Rule('a', '', '', 'b', 128);

        self::assertFalse($rule1->hasLanguageRestriction());
        self::assertTrue($rule2->hasLanguageRestriction());
    }

    #[Test]
    public function it_applies_to_language_with_any(): void
    {
        $rule = new Rule('a', '', '', 'b', 160, Rule::LOGICAL_ANY); // German + English

        self::assertTrue($rule->appliesToLanguage(128)); // German
        self::assertTrue($rule->appliesToLanguage(32));  // English
        self::assertTrue($rule->appliesToLanguage(160)); // Both
        self::assertFalse($rule->appliesToLanguage(64)); // French
    }

    #[Test]
    public function it_applies_to_language_with_all(): void
    {
        $rule = new Rule('a', '', '', 'b', 160, Rule::LOGICAL_ALL); // German + English

        self::assertFalse($rule->appliesToLanguage(128)); // German only
        self::assertFalse($rule->appliesToLanguage(32));  // English only
        self::assertTrue($rule->appliesToLanguage(160));  // Both
        self::assertTrue($rule->appliesToLanguage(224));  // Both + French
    }

    #[Test]
    public function it_gets_pattern_length(): void
    {
        $rule = new Rule('sch', '', '', 'S');

        self::assertSame(3, $rule->patternLength());
    }

    #[Test]
    public function it_matches_pattern(): void
    {
        $rule = new Rule('sch', '', '', 'S');

        self::assertTrue($rule->matchesPattern('schule', 0));
        self::assertTrue($rule->matchesPattern('aschule', 1));
        self::assertTrue($rule->matchesPattern('school', 0));  // 'school' starts with 'sch'
        self::assertFalse($rule->matchesPattern('scale', 0));  // 'scale' doesn't contain 'sch'
        self::assertFalse($rule->matchesPattern('sc', 0));     // Too short
    }

    #[Test]
    public function it_matches_left_context(): void
    {
        $rule = new Rule('sch', '[aeiou]', '', 'S');

        self::assertTrue($rule->matchesLeftContext('asch', 1));
        self::assertTrue($rule->matchesLeftContext('esch', 1));
        self::assertFalse($rule->matchesLeftContext('bsch', 1));
        self::assertFalse($rule->matchesLeftContext('sch', 0));
    }

    #[Test]
    public function it_matches_right_context(): void
    {
        $rule = new Rule('sch', '', '[ei]', 'S');

        self::assertTrue($rule->matchesRightContext('sche', 0));
        self::assertTrue($rule->matchesRightContext('schi', 0));
        self::assertFalse($rule->matchesRightContext('scha', 0));
        self::assertFalse($rule->matchesRightContext('sch', 0));
    }

    #[Test]
    public function it_matches_fully(): void
    {
        $rule = new Rule('sch', '[aeiou]', '[ei]', 'S', 128, Rule::LOGICAL_ANY);

        self::assertTrue($rule->matches('asche', 1, 128));
        self::assertFalse($rule->matches('bsche', 1, 128)); // Left context fails
        self::assertFalse($rule->matches('ascha', 1, 128)); // Right context fails
        self::assertFalse($rule->matches('asche', 1, 64));  // Language fails
    }

    #[Test]
    public function it_creates_from_array_with_missing_data(): void
    {
        // Test with empty array
        $rule = Rule::fromArray([]);
        self::assertSame('', $rule->pattern);
        self::assertSame('', $rule->leftContext);
        self::assertSame('', $rule->rightContext);
        self::assertSame('', $rule->phonetic);
        self::assertNull($rule->languageMask);
        self::assertSame(Rule::LOGICAL_ANY, $rule->logicalOp);
    }

    #[Test]
    public function it_creates_from_array_with_invalid_types(): void
    {
        // Test with wrong types (should fallback to defaults)
        $rule = Rule::fromArray([123, null, true, [], 'not-int', 456]);
        self::assertSame('', $rule->pattern);
        self::assertSame('', $rule->leftContext);
        self::assertSame('', $rule->rightContext);
        self::assertSame('', $rule->phonetic);
        self::assertNull($rule->languageMask);
        self::assertSame(Rule::LOGICAL_ANY, $rule->logicalOp);
    }

    #[Test]
    public function it_converts_to_array_without_language_mask(): void
    {
        $rule = new Rule('sch', '', '', 'S');
        $array = $rule->toArray();

        self::assertSame(['sch', '', '', 'S'], $array);
        self::assertCount(4, $array);
    }

    #[Test]
    public function it_converts_to_json_with_all_fields(): void
    {
        $rule = new Rule('sch', '[aeiou]', '[ei]', 'S', 128, 'ALL');
        $json = $rule->toJson();

        self::assertSame('sch', $json['pattern']);
        self::assertSame('S', $json['phonetic']);
        self::assertSame('[aeiou]', $json['leftContext']);
        self::assertSame('[ei]', $json['rightContext']);
        self::assertSame(128, $json['languageMask']);
        self::assertSame('ALL', $json['logicalOp']);
    }

    #[Test]
    public function it_creates_from_json_with_minimal_data(): void
    {
        $rule = Rule::fromJson([
            'pattern' => 'a',
            'phonetic' => 'b',
        ]);

        self::assertSame('a', $rule->pattern);
        self::assertSame('b', $rule->phonetic);
        self::assertSame('', $rule->leftContext);
        self::assertSame('', $rule->rightContext);
        self::assertNull($rule->languageMask);
        self::assertSame(Rule::LOGICAL_ANY, $rule->logicalOp);
    }

    #[Test]
    public function it_matches_pattern_when_pattern_too_long(): void
    {
        $rule = new Rule('abcde', '', '', 'x');

        self::assertFalse($rule->matchesPattern('abc', 0)); // Pattern longer than input
        self::assertFalse($rule->matchesPattern('abcde', 1)); // Not enough chars after position
    }

    #[Test]
    public function it_matches_with_no_language_restriction(): void
    {
        $rule = new Rule('a', '', '', 'b'); // No language mask

        self::assertTrue($rule->appliesToLanguage(128));
        self::assertTrue($rule->appliesToLanguage(1));
        self::assertTrue($rule->appliesToLanguage(999999));
    }

    #[Test]
    public function it_matches_fully_without_contexts(): void
    {
        $rule = new Rule('a', '', '', 'b');

        self::assertTrue($rule->matches('abc', 0, 1));
        self::assertTrue($rule->matches('bac', 1, 1));
        self::assertFalse($rule->matches('bcd', 0, 1)); // Pattern doesn't match
    }

    #[Test]
    public function it_checks_context_with_right_context_only(): void
    {
        $rule = new Rule('a', '', '[x]', 'b');

        self::assertTrue($rule->hasContext());
    }
}
