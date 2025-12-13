<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Enums;

/**
 * Match accuracy modes for phonetic encoding.
 *
 * - Exact: Produces fewer false positives, suitable for strict matching
 * - Approximate: Produces more matches, suitable for fuzzy searching
 */
enum MatchAccuracy: string
{
    case Exact = 'exact';
    case Approximate = 'approx';

    /**
     * Get a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Exact => 'Exact',
            self::Approximate => 'Approximate',
        };
    }

    /**
     * Get a description of this accuracy mode.
     */
    public function description(): string
    {
        return match ($this) {
            self::Exact => 'Produces fewer matches with higher precision',
            self::Approximate => 'Produces more matches with broader coverage',
        };
    }

    /**
     * Create from string value (case-insensitive).
     */
    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'exact' => self::Exact,
            'approx', 'approximate' => self::Approximate,
            default => self::Approximate,
        };
    }

    /**
     * Check if this is the Approximate mode.
     */
    public function isApproximate(): bool
    {
        return $this === self::Approximate;
    }

    /**
     * Check if this is the Exact mode.
     */
    public function isExact(): bool
    {
        return $this === self::Exact;
    }
}
