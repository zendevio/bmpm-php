<?php

declare(strict_types=1);

namespace Zendevio\BMPM;

use function count;

use Zendevio\BMPM\Contracts\LanguageDetectorInterface;
use Zendevio\BMPM\Contracts\PhoneticEncoderInterface;
use Zendevio\BMPM\Engine\LanguageDetector;
use Zendevio\BMPM\Engine\PhoneticEngine;
use Zendevio\BMPM\Enums\Language;
use Zendevio\BMPM\Enums\MatchAccuracy;
use Zendevio\BMPM\Enums\NameType;
use Zendevio\BMPM\Rules\RuleLoader;
use Zendevio\BMPM\Soundex\DaitchMokotoffSoundex;

/**
 * Main facade for the Beider-Morse Phonetic Matching library.
 *
 * This class provides a simple, fluent interface to the BMPM algorithm.
 *
 * Example usage:
 * ```php
 * // Simple usage
 * $encoder = new BeiderMorse();
 * $phonetic = $encoder->encode('Schwarzenegger');
 *
 * // Configured usage
 * $encoder = BeiderMorse::create()
 *     ->withNameType(NameType::Generic)
 *     ->withAccuracy(MatchAccuracy::Approximate);
 * $phonetic = $encoder->encode('Müller');
 *
 * // Get all alternatives as array
 * $alternatives = $encoder->encodeToArray('Kowalski');
 * ```
 *
 * @author Alexander Beider - Original algorithm
 * @author Stephen P. Morse - Original algorithm
 * @author Alin M. Gheorghe - PHP 8.4+ implementation
 *
 * @see https://stevemorse.org/phoneticinfo.htm
 */
final class BeiderMorse
{
    private NameType $nameType = NameType::Generic;

    private MatchAccuracy $accuracy = MatchAccuracy::Approximate;

    private ?int $languageMask = null;

    private ?PhoneticEncoderInterface $engine = null;

    private ?LanguageDetectorInterface $languageDetector = null;

    private ?DaitchMokotoffSoundex $soundex = null;

    private ?string $dataPath = null;

    /**
     * Create a new BeiderMorse instance (fluent factory).
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the name type variant.
     *
     * @param NameType $nameType Generic, Ashkenazic, or Sephardic
     */
    public function withNameType(NameType $nameType): self
    {
        $clone = clone $this;
        $clone->nameType = $nameType;
        $clone->engine = null; // Reset engine to apply new settings

        return $clone;
    }

    /**
     * Set the matching accuracy mode.
     *
     * @param MatchAccuracy $accuracy Exact or Approximate
     */
    public function withAccuracy(MatchAccuracy $accuracy): self
    {
        $clone = clone $this;
        $clone->accuracy = $accuracy;

        return $clone;
    }

    /**
     * Restrict encoding to specific language(s).
     *
     * @param Language ...$languages One or more languages to restrict to
     */
    public function withLanguages(Language ...$languages): self
    {
        $clone = clone $this;
        $clone->languageMask = Language::combineMask($languages);

        return $clone;
    }

    /**
     * Set language restriction using a bitmask.
     *
     * @param int $mask Language bitmask (combine Language::X->value with |)
     */
    public function withLanguageMask(int $mask): self
    {
        $clone = clone $this;
        $clone->languageMask = $mask;

        return $clone;
    }

    /**
     * Clear any language restrictions (auto-detect).
     */
    public function withAutoLanguageDetection(): self
    {
        $clone = clone $this;
        $clone->languageMask = null;

        return $clone;
    }

    /**
     * Set a custom data path for rule files.
     */
    public function withDataPath(string $path): self
    {
        $clone = clone $this;
        $clone->dataPath = $path;
        $clone->engine = null; // Reset to apply new path

        return $clone;
    }

    /**
     * Encode a name to its phonetic representation.
     *
     * @param string $name The name to encode
     *
     * @return string Phonetic encoding (may contain alternates in parentheses)
     */
    public function encode(string $name): string
    {
        return $this->getEngine()->encode(
            $name,
            $this->nameType,
            $this->accuracy,
            $this->languageMask,
        );
    }

    /**
     * Encode a name and return all alternatives as an array.
     *
     * @param string $name The name to encode
     *
     * @return array<string> Array of all phonetic alternatives
     */
    public function encodeToArray(string $name): array
    {
        return $this->getEngine()->encodeToArray(
            $name,
            $this->nameType,
            $this->accuracy,
            $this->languageMask,
        );
    }

    /**
     * Encode multiple names in batch.
     *
     * @param array<string> $names Array of names to encode
     *
     * @return array<string, string> Associative array of name => phonetic encoding
     */
    public function encodeBatch(array $names): array
    {
        return $this->getEngine()->encodeBatch(
            $names,
            $this->nameType,
            $this->accuracy,
            $this->languageMask,
        );
    }

    /**
     * Detect the language(s) of a name.
     *
     * @param string $name The name to analyze
     *
     * @return array<Language> Array of detected languages
     */
    public function detectLanguages(string $name): array
    {
        return $this->getLanguageDetector()->detectLanguages($name, $this->nameType);
    }

    /**
     * Detect the primary language of a name.
     *
     * @param string $name The name to analyze
     *
     * @return Language The most likely language
     */
    public function detectPrimaryLanguage(string $name): Language
    {
        return $this->getLanguageDetector()->detectPrimary($name, $this->nameType);
    }

    /**
     * Get Daitch-Mokotoff Soundex encoding.
     *
     * @param string $name The name to encode
     *
     * @return string Space-separated D-M Soundex codes
     */
    public function soundex(string $name): string
    {
        return $this->getSoundex()->encode($name);
    }

    /**
     * Check if two names might match phonetically.
     *
     * @param string $name1 First name
     * @param string $name2 Second name
     *
     * @return bool True if any phonetic alternatives match
     */
    public function matches(string $name1, string $name2): bool
    {
        $phonetic1 = $this->encodeToArray($name1);
        $phonetic2 = $this->encodeToArray($name2);

        return array_intersect($phonetic1, $phonetic2) !== [];
    }

    /**
     * Get the similarity score between two names.
     *
     * @param string $name1 First name
     * @param string $name2 Second name
     *
     * @return float Similarity score between 0.0 (no match) and 1.0 (identical)
     */
    public function similarity(string $name1, string $name2): float
    {
        $phonetic1 = $this->encodeToArray($name1);
        $phonetic2 = $this->encodeToArray($name2);

        if ($phonetic1 === [] || $phonetic2 === []) {
            return 0.0;
        }

        $intersection = array_intersect($phonetic1, $phonetic2);
        $union = array_unique(array_merge($phonetic1, $phonetic2));

        return count($intersection) / count($union);
    }

    /**
     * Get the current name type setting.
     */
    public function getNameType(): NameType
    {
        return $this->nameType;
    }

    /**
     * Get the current accuracy setting.
     */
    public function getAccuracy(): MatchAccuracy
    {
        return $this->accuracy;
    }

    /**
     * Get the current language mask.
     */
    public function getLanguageMask(): ?int
    {
        return $this->languageMask;
    }

    /**
     * Get available languages for the current name type.
     *
     * @return array<Language>
     */
    public function getAvailableLanguages(): array
    {
        return Language::forNameType($this->nameType);
    }

    /**
     * Get or create the phonetic engine.
     */
    private function getEngine(): PhoneticEncoderInterface
    {
        if (!$this->engine instanceof \Zendevio\BMPM\Contracts\PhoneticEncoderInterface) {
            $ruleLoader = $this->dataPath !== null
                ? new RuleLoader($this->dataPath)
                : RuleLoader::create();

            $this->engine = new PhoneticEngine(
                $ruleLoader,
                $this->getLanguageDetector(),
            );
        }

        return $this->engine;
    }

    /**
     * Get or create the language detector.
     */
    private function getLanguageDetector(): LanguageDetectorInterface
    {
        if (!$this->languageDetector instanceof \Zendevio\BMPM\Contracts\LanguageDetectorInterface) {
            $ruleLoader = $this->dataPath !== null
                ? new RuleLoader($this->dataPath)
                : RuleLoader::create();

            $this->languageDetector = new LanguageDetector($ruleLoader);
        }

        return $this->languageDetector;
    }

    /**
     * Get or create the D-M Soundex encoder.
     */
    private function getSoundex(): DaitchMokotoffSoundex
    {
        if (!$this->soundex instanceof \Zendevio\BMPM\Soundex\DaitchMokotoffSoundex) {
            $this->soundex = new DaitchMokotoffSoundex();
        }

        return $this->soundex;
    }
}
