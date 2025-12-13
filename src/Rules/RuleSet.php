<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Rules;

use ArrayIterator;

use function count;

use Countable;

use function is_string;

use IteratorAggregate;
use Traversable;

/**
 * Collection of phonetic transformation rules.
 *
 * @implements IteratorAggregate<int, Rule>
 */
final readonly class RuleSet implements Countable, IteratorAggregate
{
    /**
     * @param array<Rule> $rules
     */
    public function __construct(
        private array $rules = [],
        private ?string $name = null,
    ) {}

    /**
     * Create from array of rule arrays.
     *
     * @param array<array<int, mixed>> $data
     */
    public static function fromArrays(array $data, ?string $name = null): self
    {
        $rules = [];

        foreach ($data as $item) {
            // Skip name markers (single-element arrays)
            if (count($item) === 1 && is_string($item[0])) {
                continue;
            }

            $rules[] = Rule::fromArray($item);
        }

        return new self($rules, $name);
    }

    /**
     * Create from JSON data.
     *
     * @param array{name?: string, rules: array<array<string, mixed>>} $data
     */
    public static function fromJson(array $data): self
    {
        $rules = [];

        foreach ($data['rules'] as $ruleData) {
            /** @var array{pattern: string, leftContext?: string, rightContext?: string, phonetic: string, languageMask?: int, logicalOp?: string} $ruleData */
            $rules[] = Rule::fromJson($ruleData);
        }

        return new self($rules, $data['name'] ?? null);
    }

    /**
     * Get the name/identifier of this rule set.
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Get all rules.
     *
     * @return array<Rule>
     */
    public function all(): array
    {
        return $this->rules;
    }

    /**
     * Check if rule set is empty.
     */
    public function isEmpty(): bool
    {
        return $this->rules === [];
    }

    /**
     * Get count of rules.
     */
    public function count(): int
    {
        return count($this->rules);
    }

    /**
     * Get iterator.
     *
     * @return Traversable<int, Rule>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rules);
    }

    /**
     * Convert to JSON-serializable format.
     *
     * @return array{name: string|null, rules: array<array<string, mixed>>}
     */
    public function toJson(): array
    {
        return [
            'name' => $this->name,
            'rules' => array_map(
                static fn(Rule $rule): array => $rule->toJson(),
                $this->rules
            ),
        ];
    }
}
