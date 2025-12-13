<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Rules;

use function is_int;
use function is_string;
use function strlen;

/**
 * Represents a single phonetic transformation rule.
 *
 * A rule consists of:
 * - pattern: The character sequence to match in the input
 * - leftContext: Regex pattern that must match before the pattern (empty = any)
 * - rightContext: Regex pattern that must match after the pattern (empty = any)
 * - phonetic: The replacement phonetic output (may contain alternatives)
 * - languageMask: Optional bitmask of languages this rule applies to
 * - logicalOp: "ALL" or "ANY" for language mask interpretation
 */
final readonly class Rule
{
    public const LOGICAL_ANY = 'ANY';

    public const LOGICAL_ALL = 'ALL';

    public function __construct(
        public string $pattern,
        public string $leftContext,
        public string $rightContext,
        public string $phonetic,
        public ?int $languageMask = null,
        public string $logicalOp = self::LOGICAL_ANY,
    ) {}

    /**
     * Create from array format (as used in original PHP files).
     *
     * Array format: [pattern, leftContext, rightContext, phonetic, ?languageMask, ?logicalOp]
     *
     * @param array<int, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            pattern: isset($data[0]) && is_string($data[0]) ? $data[0] : '',
            leftContext: isset($data[1]) && is_string($data[1]) ? $data[1] : '',
            rightContext: isset($data[2]) && is_string($data[2]) ? $data[2] : '',
            phonetic: isset($data[3]) && is_string($data[3]) ? $data[3] : '',
            languageMask: isset($data[4]) && is_int($data[4]) ? $data[4] : null,
            logicalOp: isset($data[5]) && is_string($data[5]) ? $data[5] : self::LOGICAL_ANY,
        );
    }

    /**
     * Create from JSON object.
     *
     * @param array{pattern: string, leftContext?: string, rightContext?: string, phonetic: string, languageMask?: int, logicalOp?: string} $data
     */
    public static function fromJson(array $data): self
    {
        return new self(
            pattern: $data['pattern'],
            leftContext: $data['leftContext'] ?? '',
            rightContext: $data['rightContext'] ?? '',
            phonetic: $data['phonetic'],
            languageMask: $data['languageMask'] ?? null,
            logicalOp: $data['logicalOp'] ?? self::LOGICAL_ANY,
        );
    }

    /**
     * Convert to array format.
     *
     * @return array<mixed>
     */
    public function toArray(): array
    {
        $result = [
            $this->pattern,
            $this->leftContext,
            $this->rightContext,
            $this->phonetic,
        ];

        if ($this->languageMask !== null) {
            $result[] = $this->languageMask;
            $result[] = $this->logicalOp;
        }

        return $result;
    }

    /**
     * Convert to JSON-serializable array.
     *
     * @return array<string, mixed>
     */
    public function toJson(): array
    {
        $result = [
            'pattern' => $this->pattern,
            'phonetic' => $this->phonetic,
        ];

        if ($this->leftContext !== '') {
            $result['leftContext'] = $this->leftContext;
        }

        if ($this->rightContext !== '') {
            $result['rightContext'] = $this->rightContext;
        }

        if ($this->languageMask !== null) {
            $result['languageMask'] = $this->languageMask;
            $result['logicalOp'] = $this->logicalOp;
        }

        return $result;
    }

    /**
     * Check if this rule has context requirements.
     */
    public function hasContext(): bool
    {
        return $this->leftContext !== '' || $this->rightContext !== '';
    }

    /**
     * Check if this rule has language restrictions.
     */
    public function hasLanguageRestriction(): bool
    {
        return $this->languageMask !== null;
    }

    /**
     * Check if this rule applies to the given language mask.
     */
    public function appliesToLanguage(int $languageMask): bool
    {
        if ($this->languageMask === null) {
            return true; // No restriction
        }

        if ($this->logicalOp === self::LOGICAL_ALL) {
            // All specified languages must be present
            return ($languageMask & $this->languageMask) === $this->languageMask;
        }

        // ANY: At least one specified language must be present
        return ($languageMask & $this->languageMask) !== 0;
    }

    /**
     * Get the length of the pattern.
     */
    public function patternLength(): int
    {
        return strlen($this->pattern);
    }

    /**
     * Check if pattern matches at position in input.
     */
    public function matchesPattern(string $input, int $position): bool
    {
        $patternLength = $this->patternLength();

        if ($patternLength > strlen($input) - $position) {
            return false;
        }

        return substr($input, $position, $patternLength) === $this->pattern;
    }

    /**
     * Check if left context matches.
     */
    public function matchesLeftContext(string $input, int $position): bool
    {
        if ($this->leftContext === '') {
            return true;
        }

        $left = substr($input, 0, $position);
        $pattern = '/' . $this->leftContext . '$/u';

        return preg_match($pattern, $left) === 1;
    }

    /**
     * Check if right context matches.
     */
    public function matchesRightContext(string $input, int $position): bool
    {
        if ($this->rightContext === '') {
            return true;
        }

        $right = substr($input, $position + $this->patternLength());
        $pattern = '/^' . $this->rightContext . '/u';

        return preg_match($pattern, $right) === 1;
    }

    /**
     * Check if this rule fully matches at the given position.
     */
    public function matches(string $input, int $position, int $languageMask = 1): bool
    {
        // Check language restriction first (fast)
        if (!$this->appliesToLanguage($languageMask)) {
            return false;
        }

        // Check pattern match
        if (!$this->matchesPattern($input, $position)) {
            return false;
        }

        // Check contexts
        return $this->matchesLeftContext($input, $position)
            && $this->matchesRightContext($input, $position);
    }
}
