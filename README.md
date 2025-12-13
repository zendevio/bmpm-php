# BMPM - Beider-Morse Phonetic Matching for PHP

[![PHP Version](https://img.shields.io/badge/php-%5E8.4-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--3.0-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-400%20passing-brightgreen.svg)]()
[![Coverage](https://img.shields.io/badge/coverage-97.51%25-brightgreen.svg)]()
[![MSI](https://img.shields.io/badge/MSI-81%25-brightgreen.svg)]()
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)]()

A modern PHP 8.4+ implementation of the **Beider-Morse Phonetic Matching (BMPM)** algorithm for multilingual name matching. This library enables phonetic comparison of names across 20+ languages, making it ideal for genealogical research, record linkage, and fuzzy name searching.

## Features

- **Multilingual Support**: 20 languages including Arabic, Cyrillic, Greek, Hebrew, and Latin-based scripts
- **Three Name Type Modes**: Generic, Ashkenazic, and Sephardic variants
- **Dual Matching Accuracy**: Exact and Approximate modes for precision vs. recall trade-offs
- **Daitch-Mokotoff Soundex**: Included D-M Soundex implementation for Slavic/Yiddish names
- **Modern PHP**: Built for PHP 8.4+ with enums, readonly classes, and strict typing
- **Immutable API**: Fluent, immutable builder pattern for safe configuration
- **Well Tested**: 400+ tests with 97.51% coverage, 81% MSI (mutation score), PHPStan level max

## Installation

```bash
composer require zendevio/bmpm-php
```

### Requirements

- PHP 8.4 or higher
- `ext-mbstring` - Multibyte string support
- `ext-intl` - Internationalization support
- `ext-json` - JSON support

## Quick Start

```php
use Zendevio\BMPM\BeiderMorse;

// Simple usage
$encoder = new BeiderMorse();
$phonetic = $encoder->encode('Schwarzenegger');
// Returns: "(Svarcenegr|## more alternatives...)"

// Check if two names might match
$matches = $encoder->matches('Smith', 'Schmidt');
// Returns: true (they share phonetic codes)

// Get similarity score
$similarity = $encoder->similarity('Mueller', 'Miller');
// Returns: float between 0.0 and 1.0
```

## Configuration

```php
use Zendevio\BMPM\BeiderMorse;
use Zendevio\BMPM\Enums\NameType;
use Zendevio\BMPM\Enums\MatchAccuracy;
use Zendevio\BMPM\Enums\Language;

// Fluent configuration
$encoder = BeiderMorse::create()
    ->withNameType(NameType::Ashkenazic)      // Generic, Ashkenazic, or Sephardic
    ->withAccuracy(MatchAccuracy::Approximate) // Exact or Approximate
    ->withLanguages(Language::German, Language::Polish);

// Encode to array of alternatives
$alternatives = $encoder->encodeToArray('Kowalski');
// Returns: ['kovalski', 'kovalske', ...]

// Batch encoding
$results = $encoder->encodeBatch(['Smith', 'Jones', 'Williams']);
// Returns: ['Smith' => '(smit|...)', 'Jones' => '...', ...]
```

## Name Types

| Type | Description | Languages |
|------|-------------|-----------|
| **Generic** | General-purpose matching | 20 languages |
| **Ashkenazic** | Eastern European Jewish names | 11 languages |
| **Sephardic** | Mediterranean Jewish names | 6 languages |

```php
// Ashkenazic mode for Eastern European Jewish names
$encoder = BeiderMorse::create()
    ->withNameType(NameType::Ashkenazic);

// Sephardic mode for Spanish/Portuguese Jewish names
$encoder = BeiderMorse::create()
    ->withNameType(NameType::Sephardic);
```

## Language Detection

The library automatically detects the likely language(s) of a name:

```php
$encoder = new BeiderMorse();

// Detect all possible languages
$languages = $encoder->detectLanguages('Müller');
// Returns: [Language::German]

// Get primary language
$primary = $encoder->detectPrimaryLanguage('Kowalski');
// Returns: Language::Polish
```

## Daitch-Mokotoff Soundex

For Slavic and Yiddish surname matching:

```php
$encoder = new BeiderMorse();

$soundex = $encoder->soundex('Schwarzenegger');
// Returns: "479465 474659" (multiple codes for ambiguous spellings)

$soundex = $encoder->soundex('Cohen');
// Returns: "560000 460000"
```

## Advanced Usage

### Restrict to Specific Languages

```php
$encoder = BeiderMorse::create()
    ->withLanguages(Language::German, Language::English, Language::French);

// Or using a bitmask directly
$encoder = BeiderMorse::create()
    ->withLanguageMask(Language::German->value | Language::English->value);
```

### Custom Data Path

```php
// Use custom rule files location
$encoder = BeiderMorse::create()
    ->withDataPath('/path/to/custom/rules');
```

### Direct Engine Access

For advanced use cases, access the engine directly:

```php
use Zendevio\BMPM\Engine\PhoneticEngine;
use Zendevio\BMPM\Engine\LanguageDetector;
use Zendevio\BMPM\Rules\RuleLoader;

$ruleLoader = RuleLoader::create();
$detector = new LanguageDetector($ruleLoader);
$engine = new PhoneticEngine($ruleLoader, $detector);

$result = $engine->encode('name', NameType::Generic, MatchAccuracy::Approximate);
```

## API Reference

### BeiderMorse (Main Facade)

| Method | Description |
|--------|-------------|
| `encode(string $name): string` | Encode name to phonetic representation |
| `encodeToArray(string $name): array` | Get all phonetic alternatives as array |
| `encodeBatch(array $names): array` | Encode multiple names at once |
| `matches(string $a, string $b): bool` | Check if two names match phonetically |
| `similarity(string $a, string $b): float` | Get similarity score (0.0 - 1.0) |
| `detectLanguages(string $name): array` | Detect possible languages |
| `detectPrimaryLanguage(string $name): Language` | Get most likely language |
| `soundex(string $name): string` | Get D-M Soundex encoding |

### Configuration Methods

| Method | Description |
|--------|-------------|
| `withNameType(NameType $type): self` | Set name type variant |
| `withAccuracy(MatchAccuracy $accuracy): self` | Set matching accuracy |
| `withLanguages(Language ...$langs): self` | Restrict to specific languages |
| `withLanguageMask(int $mask): self` | Set language bitmask directly |
| `withAutoLanguageDetection(): self` | Enable automatic detection |
| `withDataPath(string $path): self` | Set custom rules path |

## Supported Languages

### Generic Mode (20 languages)
Arabic, Cyrillic, Czech, Dutch, English, French, German, Greek, Greek (Latin), Hebrew, Hungarian, Italian, Latvian, Polish, Portuguese, Romanian, Russian, Spanish, Turkish

### Ashkenazic Mode (11 languages)
Cyrillic, English, French, German, Hebrew, Hungarian, Polish, Romanian, Russian, Spanish

### Sephardic Mode (6 languages)
French, Hebrew, Italian, Portuguese, Spanish

## How It Works

The Beider-Morse algorithm:

1. **Language Detection**: Analyzes spelling patterns to identify likely source language(s)
2. **Phonetic Rules**: Applies language-specific transformation rules
3. **Approximation**: Generates phonetic codes that capture pronunciation variants
4. **Multi-output**: Produces multiple codes for ambiguous spellings

This enables matching names like:
- "Smith" ↔ "Schmidt" ↔ "Schmitt"
- "Cohen" ↔ "Kohn" ↔ "Cohn" ↔ "Cahan"
- "Schwarzenegger" ↔ "Shvarceneger"

## Documentation

- [Getting Started](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Advanced Usage](docs/advanced-usage.md)
- [Contributing](CONTRIBUTING.md)
- [Changelog](CHANGELOG.md)

## Testing & Quality

```bash
# Run tests
composer test

# Run with coverage
composer test:coverage

# Static analysis (PHPStan level max)
composer analyse

# Code style check
composer cs-check

# Code style fix
composer cs-fix

# Mutation testing (min 80% MSI required)
composer infection

# Automated refactoring
composer rector:dry    # Preview changes
composer rector        # Apply changes

# Run all checks
composer check         # cs-check, analyse, test

# Full CI pipeline
composer ci            # security, cs-check, analyse, test, infection
```

## Credits

- **Alexander Beider** - Original BMPM algorithm
- **Stephen P. Morse** - Original BMPM algorithm and [website](https://stevemorse.org/phoneticinfo.htm)
- **Alin M. Gheorghe** - PHP 8.4+ implementation

## License

This library is licensed under the [GPL-3.0 License](LICENSE), the same license as the original BMPM implementation.

## Related Resources

- [BMPM Official Website](https://stevemorse.org/phoneticinfo.htm)
- [One-Step Phonetic Search Tool](https://stevemorse.org/phonetics/bmpm.htm)
- [Algorithm Description (PDF)](https://stevemorse.org/phonetics/bmpm.pdf)
