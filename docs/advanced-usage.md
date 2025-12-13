# Advanced Usage

This guide covers advanced use cases and customization options for the BMPM library.

## Table of Contents

- [Direct Engine Access](#direct-engine-access)
- [Custom Rule Files](#custom-rule-files)
- [Performance Optimization](#performance-optimization)
- [Language Bitmasks](#language-bitmasks)
- [Phonetic Expansion](#phonetic-expansion)
- [Building a Name Matcher](#building-a-name-matcher)
- [Integration Patterns](#integration-patterns)

---

## Direct Engine Access

For advanced use cases, you can bypass the `BeiderMorse` facade and work directly with the engine components.

### Using PhoneticEngine Directly

```php
use Zendevio\BMPM\Engine\PhoneticEngine;
use Zendevio\BMPM\Engine\LanguageDetector;
use Zendevio\BMPM\Rules\RuleLoader;
use Zendevio\BMPM\Enums\NameType;
use Zendevio\BMPM\Enums\MatchAccuracy;

// Create components
$ruleLoader = RuleLoader::create();
$languageDetector = new LanguageDetector($ruleLoader);
$engine = new PhoneticEngine($ruleLoader, $languageDetector);

// Encode with full control
$phonetic = $engine->encode(
    input: 'Schwarzenegger',
    nameType: NameType::Generic,
    accuracy: MatchAccuracy::Approximate,
    languageMask: null  // Auto-detect
);
```

### Custom Language Detector

You can implement your own language detection:

```php
use Zendevio\BMPM\Contracts\LanguageDetectorInterface;
use Zendevio\BMPM\Enums\Language;
use Zendevio\BMPM\Enums\NameType;

class CustomLanguageDetector implements LanguageDetectorInterface
{
    public function detect(string $name, NameType $nameType = NameType::Generic): int
    {
        // Your custom detection logic
        if (str_contains($name, 'owski')) {
            return Language::Polish->value;
        }
        return Language::Any->value;
    }

    public function detectLanguages(string $name, NameType $nameType = NameType::Generic): array
    {
        return Language::fromMask($this->detect($name, $nameType));
    }

    public function detectPrimary(string $name, NameType $nameType = NameType::Generic): Language
    {
        $languages = $this->detectLanguages($name, $nameType);
        return $languages[0] ?? Language::Any;
    }
}

// Use with engine
$engine = new PhoneticEngine($ruleLoader, new CustomLanguageDetector());
```

---

## Custom Rule Files

### Rule File Structure

Rule files are JSON with this structure:

```json
{
    "name": "Rules German",
    "description": "German phonetic rules",
    "version": "3.15",
    "rules": [
        {
            "pattern": "sch",
            "leftContext": "",
            "rightContext": "",
            "phonetic": "S"
        },
        {
            "pattern": "ch",
            "leftContext": "",
            "rightContext": "[ei]",
            "phonetic": "(x|tS)",
            "languageMask": 128
        }
    ]
}
```

### Rule Fields

| Field | Required | Description |
|-------|----------|-------------|
| `pattern` | Yes | Character sequence to match |
| `phonetic` | Yes | Replacement output (may contain `(a|b)` alternatives) |
| `leftContext` | No | Regex that must match before pattern |
| `rightContext` | No | Regex that must match after pattern |
| `languageMask` | No | Bitmask of languages this rule applies to |
| `logicalOp` | No | `"ANY"` (default) or `"ALL"` for mask interpretation |

### Context Patterns

Contexts use regex patterns:

```json
{
    "pattern": "c",
    "leftContext": "",
    "rightContext": "[ei]",
    "phonetic": "s"
}
```

This matches `c` only when followed by `e` or `i`.

Common context patterns:
- `^` - Start of word
- `$` - End of word
- `[aeiou]` - Any vowel
- `[^aeiou]` - Any consonant
- `.` - Any character

### Using Custom Rules

```php
use Zendevio\BMPM\Rules\RuleLoader;

// Point to custom rules directory
$ruleLoader = new RuleLoader('/path/to/custom/rules');

// Directory structure expected:
// /path/to/custom/rules/
//   Generic/
//     rules_german.json
//     rules_english.json
//     approx_common.json
//     exact_common.json
//     language_rules.json
//   Ashkenazic/
//     ...
//   Sephardic/
//     ...
```

---

## Performance Optimization

### Caching

The library caches loaded rules internally. For long-running processes, you can clear caches:

```php
$encoder = new BeiderMorse();

// Process many names...

// Clear caches if memory is a concern
// (The engine is accessed via reflection or direct instantiation)
```

### Batch Processing

Use batch encoding for multiple names:

```php
// More efficient than individual calls
$results = $encoder->encodeBatch($thousandsOfNames);
```

### Reuse Encoder Instance

```php
// Create once, reuse many times
$encoder = BeiderMorse::create()
    ->withNameType(NameType::Generic)
    ->withAccuracy(MatchAccuracy::Approximate);

// Reuse for all encodings
foreach ($names as $name) {
    $phonetic = $encoder->encode($name);
}
```

### Pre-filter by Language

If you know the language, restrict the encoder:

```php
// Faster: only apply German rules
$encoder = BeiderMorse::create()
    ->withLanguages(Language::German);

$phonetic = $encoder->encode('Müller');
```

---

## Language Bitmasks

Languages are represented as powers of 2, allowing efficient combination:

### Combining Languages

```php
use Zendevio\BMPM\Enums\Language;

// Using enum method
$mask = Language::combineMask([
    Language::German,
    Language::Polish,
    Language::Russian
]);

// Using bitwise OR
$mask = Language::German->value
      | Language::Polish->value
      | Language::Russian->value;

// Result: 128 | 16384 | 131072 = 147584
```

### Checking Languages in Mask

```php
$mask = $encoder->detectLanguages('Kowalski');

// Check if specific language is present
if (Language::Polish->isInMask($mask)) {
    echo "Likely Polish name";
}

// Get all languages from mask
$languages = Language::fromMask($mask);
foreach ($languages as $lang) {
    echo $lang->label() . "\n";
}
```

### Language Values Reference

| Language | Value | Binary |
|----------|-------|--------|
| Any | 1 | 00000000001 |
| Arabic | 2 | 00000000010 |
| Cyrillic | 4 | 00000000100 |
| Czech | 8 | 00000001000 |
| Dutch | 16 | 00000010000 |
| English | 32 | 00000100000 |
| French | 64 | 00001000000 |
| German | 128 | 00010000000 |
| Greek | 256 | 00100000000 |
| GreekLatin | 512 | 01000000000 |
| Hebrew | 1024 | 10000000000 |
| ... | ... | ... |

---

## Phonetic Expansion

### Understanding Alternatives

BMPM output often contains alternatives:

```
(a|b)c(d|e) = acd, ace, bcd, bce
```

### Expanding Alternatives

```php
use Zendevio\BMPM\Util\PhoneticExpander;

$phonetic = "(sm|Sm)(i|Y)t";

// Expand to array
$alternatives = PhoneticExpander::expand($phonetic);
// ['smit', 'smYt', 'Smit', 'SmYt']

// Count alternatives
$count = PhoneticExpander::countAlternatives($phonetic);
// 4

// Check if has alternatives
$hasAlts = PhoneticExpander::hasAlternates($phonetic);
// true
```

### Collapsing Back

```php
$alternatives = ['smit', 'smYt', 'Smit', 'SmYt'];

$collapsed = PhoneticExpander::collapse($alternatives);
// "(smit|smYt|Smit|SmYt)"
```

### Language Attributes

Some phonetic outputs include language markers:

```php
$phonetic = "abc[128]def[32]";

// Strip attributes
$clean = PhoneticExpander::stripLanguageAttributes($phonetic);
// "abcdef"

// Normalize (AND attributes together)
$normalized = PhoneticExpander::normalizeLanguageAttributes($phonetic, false);
// "abcdef[0]" (128 & 32 = 0)
```

---

## Building a Name Matcher

### Complete Example: Database Search

```php
<?php

use Zendevio\BMPM\BeiderMorse;
use Zendevio\BMPM\Enums\NameType;
use Zendevio\BMPM\Enums\MatchAccuracy;

class PhoneticNameMatcher
{
    private BeiderMorse $encoder;
    private array $index = [];

    public function __construct(NameType $nameType = NameType::Generic)
    {
        $this->encoder = BeiderMorse::create()
            ->withNameType($nameType)
            ->withAccuracy(MatchAccuracy::Approximate);
    }

    /**
     * Index a name with its ID for later searching.
     */
    public function index(int $id, string $name): void
    {
        $codes = $this->encoder->encodeToArray($name);

        foreach ($codes as $code) {
            $this->index[$code][] = $id;
        }
    }

    /**
     * Search for names matching the query.
     */
    public function search(string $query): array
    {
        $codes = $this->encoder->encodeToArray($query);
        $matches = [];

        foreach ($codes as $code) {
            if (isset($this->index[$code])) {
                foreach ($this->index[$code] as $id) {
                    $matches[$id] = ($matches[$id] ?? 0) + 1;
                }
            }
        }

        // Sort by match count (relevance)
        arsort($matches);

        return array_keys($matches);
    }
}

// Usage
$matcher = new PhoneticNameMatcher();

// Index names from database
$matcher->index(1, 'Smith');
$matcher->index(2, 'Schmidt');
$matcher->index(3, 'Schmitt');
$matcher->index(4, 'Jones');
$matcher->index(5, 'Smythe');

// Search
$results = $matcher->search('Smith');
// Returns: [1, 2, 3, 5] (Jones excluded)
```

### Similarity Ranking

```php
class RankedNameMatcher
{
    private BeiderMorse $encoder;

    public function __construct()
    {
        $this->encoder = new BeiderMorse();
    }

    /**
     * Find and rank matches from a list.
     */
    public function findMatches(string $query, array $candidates, float $threshold = 0.1): array
    {
        $results = [];

        foreach ($candidates as $candidate) {
            $similarity = $this->encoder->similarity($query, $candidate);

            if ($similarity >= $threshold) {
                $results[] = [
                    'name' => $candidate,
                    'similarity' => $similarity,
                    'match' => $this->encoder->matches($query, $candidate),
                ];
            }
        }

        // Sort by similarity descending
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $results;
    }
}
```

---

## Integration Patterns

### Laravel Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Zendevio\BMPM\BeiderMorse;
use Zendevio\BMPM\Enums\NameType;

class BmpmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BeiderMorse::class, function () {
            return BeiderMorse::create()
                ->withNameType(NameType::Generic);
        });
    }
}
```

### Symfony Service Configuration

```yaml
# config/services.yaml
services:
    Zendevio\BMPM\BeiderMorse:
        factory: ['Zendevio\BMPM\BeiderMorse', 'create']
        calls:
            - withNameType: ['@Zendevio\BMPM\Enums\NameType::Generic']
```

### Database Index Pattern

For large datasets, pre-compute phonetic codes:

```sql
-- MySQL example
CREATE TABLE names (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    phonetic_codes TEXT,
    soundex_codes VARCHAR(100)
);

CREATE INDEX idx_phonetic ON names(phonetic_codes(255));
```

```php
// Index on insert
$name = 'Schwarzenegger';
$phonetic = $encoder->encode($name);
$soundex = $encoder->soundex($name);

$db->insert('names', [
    'name' => $name,
    'phonetic_codes' => implode('|', $encoder->encodeToArray($name)),
    'soundex_codes' => $soundex,
]);

// Search
$searchCodes = $encoder->encodeToArray('Shvartseneger');
$placeholders = implode(',', array_fill(0, count($searchCodes), '?'));

$sql = "SELECT * FROM names WHERE " . implode(' OR ',
    array_map(fn($code) => "phonetic_codes LIKE ?", $searchCodes)
);
```

---

## Troubleshooting

### Empty Results

If `encode()` returns empty:

1. Check input is valid UTF-8
2. Ensure name is not just whitespace
3. Check language detection found valid languages

```php
// Debug language detection
$languages = $encoder->detectLanguages($name);
var_dump($languages);
```

### Too Many Alternatives

If getting explosion of alternatives:

```php
// Use Exact mode for fewer results
$encoder = BeiderMorse::create()
    ->withAccuracy(MatchAccuracy::Exact);

// Or restrict languages
$encoder = BeiderMorse::create()
    ->withLanguages(Language::English);
```

### Performance Issues

For slow encoding:

1. Reuse encoder instances
2. Use batch processing
3. Restrict to known languages
4. Consider pre-computing and caching results
