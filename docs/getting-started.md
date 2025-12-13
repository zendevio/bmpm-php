# Getting Started with BMPM

This guide will help you get up and running with the Beider-Morse Phonetic Matching library.

## Installation

Install via Composer:

```bash
composer require zendevio/bmpm
```

### Requirements

- **PHP 8.2** or higher
- **ext-mbstring** - For UTF-8 string handling
- **ext-intl** - For internationalization
- **ext-json** - For rule file parsing

## Your First Phonetic Encoding

```php
<?php

require 'vendor/autoload.php';

use Zendevio\BMPM\BeiderMorse;

// Create an encoder with default settings
$encoder = new BeiderMorse();

// Encode a name
$phonetic = $encoder->encode('Schwarzenegger');
echo $phonetic;
// Output: (Svarcenegr|Svarceneger|Svarceniker|...)
```

The output contains multiple phonetic alternatives separated by `|` and grouped in parentheses. This represents all possible pronunciations of the name across different languages.

## Understanding the Output

### Phonetic Alternatives

BMPM produces multiple phonetic codes because:
- Names can originate from different languages
- Spellings are often ambiguous
- Pronunciation varies by region

```php
$alternatives = $encoder->encodeToArray('Mueller');
print_r($alternatives);
// Output: ['milr', 'mylr', 'miler', ...]
```

### Matching Names

To check if two names might be the same person:

```php
// Simple boolean match
if ($encoder->matches('Smith', 'Schmidt')) {
    echo "These names might match!";
}

// Get a similarity score (0.0 to 1.0)
$score = $encoder->similarity('Mueller', 'Miller');
echo "Similarity: " . ($score * 100) . "%";
```

## Choosing a Name Type

The library supports three name type modes, each optimized for different name origins:

### Generic (Default)

Best for general-purpose name matching across all origins:

```php
use Zendevio\BMPM\Enums\NameType;

$encoder = BeiderMorse::create()
    ->withNameType(NameType::Generic);
```

Supports 20 languages including Arabic, Cyrillic, Greek, Hebrew, and all Latin-based European languages.

### Ashkenazic

Optimized for Eastern European Jewish surnames:

```php
$encoder = BeiderMorse::create()
    ->withNameType(NameType::Ashkenazic);

$encoder->encode('Rabinowitz');  // Eastern European Jewish name
$encoder->encode('Goldstein');   // German-Jewish name
```

Supports 11 languages common in Ashkenazic Jewish communities.

### Sephardic

Optimized for Mediterranean Jewish surnames:

```php
$encoder = BeiderMorse::create()
    ->withNameType(NameType::Sephardic);

$encoder->encode('Cardozo');     // Portuguese-Jewish name
$encoder->encode('Benavides');   // Spanish-Jewish name
```

Supports 6 languages common in Sephardic Jewish communities.

## Matching Accuracy

Choose between precision and recall:

### Approximate Mode (Default)

Produces more matches, better for fuzzy searching:

```php
use Zendevio\BMPM\Enums\MatchAccuracy;

$encoder = BeiderMorse::create()
    ->withAccuracy(MatchAccuracy::Approximate);
```

### Exact Mode

Produces fewer, more precise matches:

```php
$encoder = BeiderMorse::create()
    ->withAccuracy(MatchAccuracy::Exact);
```

## Language Detection

The library can automatically detect which language(s) a name likely originates from:

```php
// Detect all possible languages
$languages = $encoder->detectLanguages('Kowalski');
// Returns: [Language::Polish]

// Get the primary (most likely) language
$primary = $encoder->detectPrimaryLanguage('Müller');
// Returns: Language::German
```

## Daitch-Mokotoff Soundex

For Slavic and Yiddish surname matching, use the included D-M Soundex:

```php
$soundex = $encoder->soundex('Schwarzenegger');
echo $soundex;
// Output: "479465 474659"
```

D-M Soundex produces 6-digit codes (vs. 4 for Russell Soundex) and can output multiple codes for ambiguous spellings.

## Batch Processing

For processing multiple names efficiently:

```php
$names = ['Smith', 'Jones', 'Williams', 'Brown'];

$results = $encoder->encodeBatch($names);
// Returns: [
//     'Smith' => '(smit|...)',
//     'Jones' => '(jonz|...)',
//     ...
// ]
```

## Complete Example

Here's a complete example showing common use cases:

```php
<?php

require 'vendor/autoload.php';

use Zendevio\BMPM\BeiderMorse;
use Zendevio\BMPM\Enums\NameType;
use Zendevio\BMPM\Enums\MatchAccuracy;
use Zendevio\BMPM\Enums\Language;

// Create a configured encoder
$encoder = BeiderMorse::create()
    ->withNameType(NameType::Generic)
    ->withAccuracy(MatchAccuracy::Approximate);

// Process a list of names
$searchName = 'Kowalski';
$databaseNames = ['Kowalsky', 'Kovalski', 'Kawalski', 'Smith', 'Jones'];

echo "Searching for matches to: $searchName\n";
echo str_repeat('-', 40) . "\n";

foreach ($databaseNames as $name) {
    $similarity = $encoder->similarity($searchName, $name);
    $matches = $encoder->matches($searchName, $name);

    printf(
        "%s: %.1f%% similarity %s\n",
        $name,
        $similarity * 100,
        $matches ? '(MATCH)' : ''
    );
}

// Output:
// Searching for matches to: Kowalski
// ----------------------------------------
// Kowalsky: 85.7% similarity (MATCH)
// Kovalski: 100.0% similarity (MATCH)
// Kawalski: 71.4% similarity (MATCH)
// Smith: 0.0% similarity
// Jones: 0.0% similarity
```

## Next Steps

- [API Reference](api-reference.md) - Complete method documentation
- [Advanced Usage](advanced-usage.md) - Custom configurations and extensions
- [Language Reference](languages.md) - Supported languages by name type
