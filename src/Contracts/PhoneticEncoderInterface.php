<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Contracts;

use Zendevio\BMPM\Enums\MatchAccuracy;
use Zendevio\BMPM\Enums\NameType;

/**
 * Contract for phonetic encoding implementations.
 */
interface PhoneticEncoderInterface
{
    /**
     * Encode a name to its phonetic representation.
     *
     * @param string $input The name to encode
     * @param NameType $nameType The name type variant to use
     * @param MatchAccuracy $accuracy The matching accuracy mode
     * @param int|null $languageMask Optional language bitmask to restrict encoding
     *
     * @return string The phonetic encoding (may contain alternates separated by |)
     */
    public function encode(
        string $input,
        NameType $nameType = NameType::Generic,
        MatchAccuracy $accuracy = MatchAccuracy::Approximate,
        ?int $languageMask = null,
    ): string;

    /**
     * Encode a name and return all alternatives as an array.
     *
     * @param string $input The name to encode
     * @param NameType $nameType The name type variant to use
     * @param MatchAccuracy $accuracy The matching accuracy mode
     * @param int|null $languageMask Optional language bitmask to restrict encoding
     *
     * @return array<string> Array of phonetic alternatives
     */
    public function encodeToArray(
        string $input,
        NameType $nameType = NameType::Generic,
        MatchAccuracy $accuracy = MatchAccuracy::Approximate,
        ?int $languageMask = null,
    ): array;

    /**
     * Encode multiple names in batch.
     *
     * @param array<string> $inputs Array of names to encode
     * @param NameType $nameType The name type variant to use
     * @param MatchAccuracy $accuracy The matching accuracy mode
     * @param int|null $languageMask Optional language bitmask to restrict encoding
     *
     * @return array<string, string> Associative array of input => phonetic encoding
     */
    public function encodeBatch(
        array $inputs,
        NameType $nameType = NameType::Generic,
        MatchAccuracy $accuracy = MatchAccuracy::Approximate,
        ?int $languageMask = null,
    ): array;
}
