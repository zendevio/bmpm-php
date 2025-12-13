<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Soundex;

use function count;
use function in_array;
use function strlen;

/**
 * Daitch-Mokotoff Soundex implementation.
 *
 * The Daitch-Mokotoff Soundex system was developed in 1985 by Gary Mokotoff
 * and Randy Daitch to improve upon the Russell Soundex system for matching
 * Slavic and Yiddish surnames.
 *
 * Unlike Russell Soundex which produces a single 4-character code, D-M Soundex
 * can produce multiple 6-digit codes for a single name due to ambiguous letter
 * combinations.
 */
final class DaitchMokotoffSoundex
{
    private const VOWELS = 'aeioujy';

    private const CODE_LENGTH = 6;

    /**
     * Main encoding rules.
     * Format: [pattern, start-of-name code, before-vowel code, other code]
     *
     * @var array<array{0: string, 1: string, 2: string, 3: string}>
     */
    private const RULES = [
        ['schtsch', '2', '4', '4'],
        ['schtsh', '2', '4', '4'],
        ['schtch', '2', '4', '4'],
        ['shtch', '2', '4', '4'],
        ['shtsh', '2', '4', '4'],
        ['stsch', '2', '4', '4'],
        ['ttsch', '4', '4', '4'],
        ['zhdzh', '2', '4', '4'],
        ['shch', '2', '4', '4'],
        ['scht', '2', '43', '43'],
        ['schd', '2', '43', '43'],
        ['stch', '2', '4', '4'],
        ['strz', '2', '4', '4'],
        ['strs', '2', '4', '4'],
        ['stsh', '2', '4', '4'],
        ['szcz', '2', '4', '4'],
        ['szcs', '2', '4', '4'],
        ['ttch', '4', '4', '4'],
        ['tsch', '4', '4', '4'],
        ['ttsz', '4', '4', '4'],
        ['zdzh', '2', '4', '4'],
        ['zsch', '4', '4', '4'],
        ['chs', '5', '54', '54'],
        ['csz', '4', '4', '4'],
        ['czs', '4', '4', '4'],
        ['drz', '4', '4', '4'],
        ['drs', '4', '4', '4'],
        ['dsh', '4', '4', '4'],
        ['dsz', '4', '4', '4'],
        ['dzh', '4', '4', '4'],
        ['dzs', '4', '4', '4'],
        ['sch', '4', '4', '4'],
        ['sht', '2', '43', '43'],
        ['szt', '2', '43', '43'],
        ['shd', '2', '43', '43'],
        ['szd', '2', '43', '43'],
        ['tch', '4', '4', '4'],
        ['trz', '4', '4', '4'],
        ['trs', '4', '4', '4'],
        ['tsh', '4', '4', '4'],
        ['tts', '4', '4', '4'],
        ['ttz', '4', '4', '4'],
        ['tzs', '4', '4', '4'],
        ['tsz', '4', '4', '4'],
        ['zdz', '2', '4', '4'],
        ['zhd', '2', '43', '43'],
        ['zsh', '4', '4', '4'],
        ['ai', '0', '1', '999'],
        ['aj', '0', '1', '999'],
        ['ay', '0', '1', '999'],
        ['au', '0', '7', '999'],
        ['cz', '4', '4', '4'],
        ['cs', '4', '4', '4'],
        ['ds', '4', '4', '4'],
        ['dz', '4', '4', '4'],
        ['dt', '3', '3', '3'],
        ['ei', '0', '1', '999'],
        ['ej', '0', '1', '999'],
        ['ey', '0', '1', '999'],
        ['eu', '1', '1', '999'],
        ['ia', '1', '999', '999'],
        ['ie', '1', '999', '999'],
        ['io', '1', '999', '999'],
        ['iu', '1', '999', '999'],
        ['ks', '5', '54', '54'],
        ['kh', '5', '5', '5'],
        ['mn', '66', '66', '66'],
        ['nm', '66', '66', '66'],
        ['oi', '0', '1', '999'],
        ['oj', '0', '1', '999'],
        ['oy', '0', '1', '999'],
        ['pf', '7', '7', '7'],
        ['ph', '7', '7', '7'],
        ['sh', '4', '4', '4'],
        ['sc', '2', '4', '4'],
        ['st', '2', '43', '43'],
        ['sd', '2', '43', '43'],
        ['sz', '4', '4', '4'],
        ['th', '3', '3', '3'],
        ['ts', '4', '4', '4'],
        ['tc', '4', '4', '4'],
        ['tz', '4', '4', '4'],
        ['ui', '0', '1', '999'],
        ['uj', '0', '1', '999'],
        ['uy', '0', '1', '999'],
        ['ue', '0', '1', '999'],
        ['zd', '2', '43', '43'],
        ['zh', '4', '4', '4'],
        ['zs', '4', '4', '4'],
        ['rz', '4', '4', '4'],
        ['ch', '5', '5', '5'],
        ['ck', '5', '5', '5'],
        ['fb', '7', '7', '7'],
        ['a', '0', '999', '999'],
        ['b', '7', '7', '7'],
        ['d', '3', '3', '3'],
        ['e', '0', '999', '999'],
        ['f', '7', '7', '7'],
        ['g', '5', '5', '5'],
        ['h', '5', '5', '999'],
        ['i', '0', '999', '999'],
        ['k', '5', '5', '5'],
        ['l', '8', '8', '8'],
        ['m', '6', '6', '6'],
        ['n', '6', '6', '6'],
        ['o', '0', '999', '999'],
        ['p', '7', '7', '7'],
        ['q', '5', '5', '5'],
        ['r', '9', '9', '9'],
        ['s', '4', '4', '4'],
        ['t', '3', '3', '3'],
        ['u', '0', '999', '999'],
        ['v', '7', '7', '7'],
        ['w', '7', '7', '7'],
        ['x', '5', '54', '54'],
        ['y', '1', '999', '999'],
        ['z', '4', '4', '4'],
        ['c', '5', '5', '5'],
        ['j', '1', '999', '999'],
    ];

    /**
     * Alternate rules for branching cases.
     * Maps pattern => [startCode, vowelCode, otherCode]
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const ALT_RULES = [
        'rz' => ['94', '94', '94'],
        'ch' => ['4', '4', '4'],
        'ck' => ['45', '45', '45'],
        'c' => ['4', '4', '4'],
        'j' => ['4', '4', '4'],
    ];

    /**
     * Encode a name using Daitch-Mokotoff Soundex.
     *
     * @return string Space-separated list of 6-digit codes
     */
    public function encode(string $name): string
    {
        // Normalize input
        $name = $this->normalizeInput($name);

        if ($name === '') {
            return '';
        }

        // Handle multiple words/parts
        $parts = $this->splitName($name);
        $results = [];

        foreach ($parts as $part) {
            $codes = $this->encodePart($part);
            foreach ($codes as $code) {
                if (!in_array($code, $results, true)) {
                    $results[] = $code;
                }
            }
        }

        return implode(' ', $results);
    }

    /**
     * Encode a single name part.
     *
     * @return array<string>
     */
    private function encodePart(string $name): array
    {
        if ($name === '') {
            return [];
        }

        // Initialize encoding arrays for branching
        $codes = [''];
        $lastCodes = [''];
        $first = true;

        while ($name !== '') {
            $matched = false;

            foreach (self::RULES as $rule) {
                [$pattern, $startCode, $vowelCode, $otherCode] = $rule;

                if (!str_starts_with($name, $pattern)) {
                    continue;
                }

                // Check if this pattern has an alternate encoding
                $hasAlt = isset(self::ALT_RULES[$pattern]);
                $altRule = $hasAlt ? $this->getAltRule($pattern) : null;

                // Determine which code to use
                $nextChar = substr($name, strlen($pattern), 1);
                $beforeVowel = $nextChar !== '' && str_contains(self::VOWELS, $nextChar);

                // Get primary code
                if ($first) {
                    $code = $startCode;
                    $altCode = $altRule?->getStartCode();
                } elseif ($beforeVowel) {
                    $code = $vowelCode;
                    $altCode = $altRule?->getVowelCode();
                } else {
                    $code = $otherCode;
                    $altCode = $altRule?->getOtherCode();
                }

                // Handle branching
                if ($hasAlt && $altCode !== null) {
                    $newCodes = [];
                    $newLastCodes = [];
                    $codesCount = count($codes);

                    for ($i = 0; $i < $codesCount; $i++) {
                        $lastCode = $lastCodes[$i] ?? '';

                        // Primary branch
                        if ($code !== '999' && $code !== $lastCode) {
                            $newCodes[] = $codes[$i] . ($code !== '999' ? $code : '');
                            $newLastCodes[] = $code;
                        } else {
                            $newCodes[] = $codes[$i];
                            $newLastCodes[] = $code === '999' ? '' : $lastCode;
                        }

                        // Alternate branch
                        if ($altCode !== '999' && $altCode !== $lastCode) {
                            $newCodes[] = $codes[$i] . ($altCode !== '999' ? $altCode : '');
                            $newLastCodes[] = $altCode;
                        } else {
                            $newCodes[] = $codes[$i];
                            $newLastCodes[] = $altCode === '999' ? '' : $lastCode;
                        }
                    }

                    $codes = $newCodes;
                    $lastCodes = $newLastCodes;
                } else {
                    // No branching
                    for ($i = 0, $count = count($codes); $i < $count; $i++) {
                        if ($code !== '999' && $code !== $lastCodes[$i]) {
                            $codes[$i] .= $code;
                            $lastCodes[$i] = $code;
                        } elseif ($code === '999') {
                            $lastCodes[$i] = '';
                        }
                    }
                }

                $name = substr($name, strlen($pattern));
                $first = false;
                $matched = true;

                break;
            }

            if (!$matched) {
                // Skip unrecognized character
                $name = substr($name, 1);
            }
        }

        // Pad/truncate to 6 digits and remove duplicates
        $results = [];
        foreach ($codes as $code) {
            $code = str_pad(substr($code, 0, self::CODE_LENGTH), self::CODE_LENGTH, '0');
            if (!in_array($code, $results, true)) {
                $results[] = $code;
            }
        }

        return $results;
    }

    /**
     * Get alternate rule for a pattern.
     * Only called when pattern exists in ALT_RULES (checked via isset() before call).
     */
    private function getAltRule(string $pattern): AltRule
    {
        $rule = self::ALT_RULES[$pattern];

        return new AltRule($rule[0], $rule[1], $rule[2]);
    }

    /**
     * Normalize input for encoding.
     */
    private function normalizeInput(string $input): string
    {
        // Remove diacritics and convert to lowercase
        $input = $this->removeDiacritics($input);
        $input = strtolower(trim($input));

        // Remove non-alphabetic characters except separators
        $input = preg_replace('/[^a-z\s\/,]/', '', $input) ?? $input;

        return $input;
    }

    /**
     * Remove diacritical marks from string.
     */
    private function removeDiacritics(string $input): string
    {
        // Basic transliteration for common diacritics
        $map = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ă' => 'a', 'ą' => 'a',
            'ç' => 'c', 'ć' => 'c', 'č' => 'c',
            'ď' => 'd',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ě' => 'e', 'ę' => 'e',
            'ğ' => 'g',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ı' => 'i',
            'ł' => 'l',
            'ñ' => 'n', 'ń' => 'n', 'ň' => 'n',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ř' => 'r',
            'ş' => 's', 'ś' => 's', 'š' => 's',
            'ţ' => 't', 'ť' => 't',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ů' => 'u', 'ű' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ź' => 'z', 'ż' => 'z', 'ž' => 'z',
            'ß' => 'ss',
            'æ' => 'ae',
            'œ' => 'oe',
        ];

        return strtr($input, $map);
    }

    /**
     * Split name into parts for separate encoding.
     *
     * @return array<string>
     */
    private function splitName(string $name): array
    {
        // Replace various separators with standard delimiter
        $name = preg_replace('/[\s,\/]+/', ' ', $name) ?? $name;

        $parts = explode(' ', $name);

        return array_filter($parts, static fn(string $s): bool => $s !== '');
    }
}

/**
 * Helper class for alternate rule codes.
 *
 * @internal
 */
final readonly class AltRule
{
    public function __construct(
        private string $startCode,
        private string $vowelCode,
        private string $otherCode,
    ) {}

    public function getStartCode(): string
    {
        return $this->startCode;
    }

    public function getVowelCode(): string
    {
        return $this->vowelCode;
    }

    public function getOtherCode(): string
    {
        return $this->otherCode;
    }
}
