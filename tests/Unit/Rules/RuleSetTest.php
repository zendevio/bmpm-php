<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit\Rules;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zendevio\BMPM\Rules\Rule;
use Zendevio\BMPM\Rules\RuleSet;

#[CoversClass(RuleSet::class)]
final class RuleSetTest extends TestCase
{
    #[Test]
    public function it_creates_empty_ruleset(): void
    {
        $ruleSet = new RuleSet();

        self::assertTrue($ruleSet->isEmpty());
        self::assertCount(0, $ruleSet);
        self::assertNull($ruleSet->name());
    }

    #[Test]
    public function it_creates_ruleset_with_rules(): void
    {
        $rules = [
            new Rule('a', '', '', 'A'),
            new Rule('b', '', '', 'B'),
        ];

        $ruleSet = new RuleSet($rules);

        self::assertFalse($ruleSet->isEmpty());
        self::assertCount(2, $ruleSet);
    }

    #[Test]
    public function it_creates_ruleset_with_name(): void
    {
        $ruleSet = new RuleSet([], 'test-rules');

        self::assertSame('test-rules', $ruleSet->name());
    }

    #[Test]
    public function it_creates_from_arrays(): void
    {
        $data = [
            ['a', '', '', 'A'],
            ['b', '', '', 'B'],
        ];

        $ruleSet = RuleSet::fromArrays($data, 'from-arrays');

        self::assertCount(2, $ruleSet);
        self::assertSame('from-arrays', $ruleSet->name());
    }

    #[Test]
    public function it_skips_name_markers_in_from_arrays(): void
    {
        $data = [
            ['marker-name'],  // Single element - should be skipped
            ['a', '', '', 'A'],
            ['b', '', '', 'B'],
        ];

        $ruleSet = RuleSet::fromArrays($data);

        self::assertCount(2, $ruleSet);
    }

    #[Test]
    public function it_creates_from_json(): void
    {
        $data = [
            'name' => 'json-rules',
            'rules' => [
                ['pattern' => 'a', 'phonetic' => 'A'],
                ['pattern' => 'b', 'phonetic' => 'B'],
            ],
        ];

        $ruleSet = RuleSet::fromJson($data);

        self::assertCount(2, $ruleSet);
        self::assertSame('json-rules', $ruleSet->name());
    }

    #[Test]
    public function it_creates_from_json_without_name(): void
    {
        $data = [
            'rules' => [
                ['pattern' => 'a', 'phonetic' => 'A'],
            ],
        ];

        $ruleSet = RuleSet::fromJson($data);

        self::assertCount(1, $ruleSet);
        self::assertNull($ruleSet->name());
    }

    #[Test]
    public function it_returns_all_rules(): void
    {
        $rules = [
            new Rule('a', '', '', 'A'),
            new Rule('b', '', '', 'B'),
        ];

        $ruleSet = new RuleSet($rules);
        $allRules = $ruleSet->all();

        self::assertCount(2, $allRules);
        self::assertSame($rules, $allRules);
    }

    #[Test]
    public function it_is_iterable(): void
    {
        $rules = [
            new Rule('a', '', '', 'A'),
            new Rule('b', '', '', 'B'),
        ];

        $ruleSet = new RuleSet($rules);

        $count = 0;
        foreach ($ruleSet as $rule) {
            self::assertInstanceOf(Rule::class, $rule);
            $count++;
        }

        self::assertSame(2, $count);
    }

    #[Test]
    public function it_is_countable(): void
    {
        $rules = [
            new Rule('a', '', '', 'A'),
            new Rule('b', '', '', 'B'),
            new Rule('c', '', '', 'C'),
        ];

        $ruleSet = new RuleSet($rules);

        self::assertCount(3, $ruleSet);
        self::assertSame(3, $ruleSet->count());
    }

    #[Test]
    public function it_converts_to_json(): void
    {
        $rules = [
            new Rule('a', '', '', 'A'),
        ];

        $ruleSet = new RuleSet($rules, 'test');
        $json = $ruleSet->toJson();

        self::assertIsArray($json);
        self::assertArrayHasKey('name', $json);
        self::assertArrayHasKey('rules', $json);
        self::assertSame('test', $json['name']);
        self::assertCount(1, $json['rules']);
    }

    #[Test]
    public function it_converts_to_json_with_null_name(): void
    {
        $rules = [
            new Rule('a', '', '', 'A'),
        ];

        $ruleSet = new RuleSet($rules);
        $json = $ruleSet->toJson();

        self::assertNull($json['name']);
    }

    #[Test]
    public function it_handles_complex_rule_data_in_from_json(): void
    {
        $data = [
            'rules' => [
                [
                    'pattern' => 'sch',
                    'leftContext' => '',
                    'rightContext' => '[ei]',
                    'phonetic' => 'S',
                    'languageMask' => 128,
                    'logicalOp' => 'ANY',
                ],
            ],
        ];

        $ruleSet = RuleSet::fromJson($data);

        self::assertCount(1, $ruleSet);

        $rules = $ruleSet->all();
        $rule = $rules[0];

        self::assertSame('sch', $rule->pattern);
        self::assertSame('[ei]', $rule->rightContext);
        self::assertSame(128, $rule->languageMask);
    }
}
