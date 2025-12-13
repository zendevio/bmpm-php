# Supported Languages

Complete reference of languages supported by each name type mode.

## Generic Mode

The Generic mode supports the widest range of languages, suitable for general-purpose name matching.

| Language | Bitmask Value | Script | Region |
|----------|---------------|--------|--------|
| Any | 1 | - | Fallback |
| Arabic | 2 | Arabic | Middle East, North Africa |
| Cyrillic | 4 | Cyrillic | Russia, Eastern Europe |
| Czech | 8 | Latin | Czech Republic |
| Dutch | 16 | Latin | Netherlands, Belgium |
| English | 32 | Latin | UK, USA, Commonwealth |
| French | 64 | Latin | France, Canada, Africa |
| German | 128 | Latin | Germany, Austria, Switzerland |
| Greek | 256 | Greek | Greece, Cyprus |
| Greek (Latin) | 512 | Latin | Greek names in Latin script |
| Hebrew | 1024 | Hebrew | Israel, Jewish communities |
| Hungarian | 2048 | Latin | Hungary |
| Italian | 4096 | Latin | Italy |
| Latvian | 8192 | Latin | Latvia |
| Polish | 16384 | Latin | Poland |
| Portuguese | 32768 | Latin | Portugal, Brazil |
| Romanian | 65536 | Latin | Romania, Moldova |
| Russian | 131072 | Cyrillic | Russia |
| Spanish | 262144 | Latin | Spain, Latin America |
| Turkish | 524288 | Latin | Turkey |

**Total: 20 languages**

## Ashkenazic Mode

Optimized for Ashkenazic Jewish surnames from Central and Eastern Europe.

| Language | Bitmask Value | Notes |
|----------|---------------|-------|
| Any | 1 | Fallback |
| Cyrillic | 4 | Russian/Ukrainian variants |
| English | 32 | Anglicized names |
| French | 64 | French variants |
| German | 128 | Most common origin |
| Hebrew | 1024 | Original Hebrew names |
| Hungarian | 2048 | Hungarian variants |
| Polish | 16384 | Polish-Jewish names |
| Romanian | 65536 | Romanian-Jewish names |
| Russian | 131072 | Russian-Jewish names |
| Spanish | 262144 | Sephardic influence |

**Total: 11 languages**

### Common Ashkenazic Name Patterns

- German: -stein, -berg, -feld, -man(n)
- Polish: -ski, -wicz, -czyk
- Russian: -ovich, -evich, -sky
- Hebrew: Cohen, Levi, variations

## Sephardic Mode

Optimized for Sephardic Jewish surnames from Spain, Portugal, and Mediterranean regions.

| Language | Bitmask Value | Notes |
|----------|---------------|-------|
| Any | 1 | Fallback |
| French | 64 | North African influence |
| Hebrew | 1024 | Original Hebrew names |
| Italian | 4096 | Italian-Jewish names |
| Portuguese | 32768 | Portuguese origin |
| Spanish | 262144 | Primary origin |

**Total: 6 languages**

### Common Sephardic Name Patterns

- Spanish: -ez, -es, de + location
- Portuguese: -es, da + location
- Italian: -i, -o endings
- Ladino variations

## Language Detection

The library automatically detects likely languages based on spelling patterns:

```php
use Zendevio\BMPM\BeiderMorse;

$encoder = new BeiderMorse();

// German pattern detected
$encoder->detectLanguages('Müller');
// → [Language::German]

// Polish pattern detected
$encoder->detectLanguages('Kowalski');
// → [Language::Polish]

// Multiple languages possible
$encoder->detectLanguages('Smith');
// → [Language::English, Language::German, ...]
```

## Detection Patterns

### Characteristic Patterns by Language

| Language | Patterns | Examples |
|----------|----------|----------|
| Arabic | -allah, -al, abd-, ibn- | Abdullah, Abdallah |
| Czech | ř, č, ž, -ová | Dvořák, Černý |
| Dutch | -ij, -ijk, van- | Dijkstra, van der Berg |
| English | -tion, -son, Mc-, O'- | Johnson, McDonald |
| French | -eau, -eux, -ault | Moreau, Dubois |
| German | -sch, -stein, ü, ö | Müller, Schmidt |
| Greek | -os, -is, -opoulos | Papadopoulos |
| Hebrew | -ah, -el, -man | Abraham, Cohen |
| Hungarian | -gy, -sz, -cs | Nagy, Kovács |
| Italian | -i, -ini, -elli | Rossi, Bianchi |
| Polish | -ski, -cki, -wicz | Kowalski, Nowak |
| Portuguese | -ão, -es, -eira | Silva, Pereira |
| Romanian | -escu, -iu | Ionescu, Popescu |
| Russian | -ov, -ev, -sky | Ivanov, Smirnov |
| Spanish | -ez, -az, -oz | Gonzalez, Rodriguez |
| Turkish | -oğlu, -lı, -ci | Yılmaz, Demir |

## Using Language Restriction

For better precision when you know the origin:

```php
use Zendevio\BMPM\Enums\Language;

// Restrict to specific language
$encoder = BeiderMorse::create()
    ->withLanguages(Language::German);

// Restrict to multiple languages
$encoder = BeiderMorse::create()
    ->withLanguages(
        Language::German,
        Language::Polish,
        Language::Russian
    );
```

## Bitmask Operations

Languages use power-of-2 values for efficient combining:

```php
// Combine languages
$mask = Language::German->value | Language::Polish->value;
// 128 | 16384 = 16512

// Check if language in mask
$hasGerman = ($mask & Language::German->value) !== 0;
// true

// Get all languages from mask
$languages = Language::fromMask($mask);
// [Language::German, Language::Polish]

// Combine using helper
$mask = Language::combineMask([
    Language::English,
    Language::French,
    Language::German
]);
```

## Script Considerations

### Latin Script Languages

Most European languages use Latin script with various diacritical marks:

- German: ä, ö, ü, ß
- French: é, è, ê, ç
- Polish: ą, ę, ł, ó, ś, ź, ż
- Czech: á, č, ď, é, ě, í, ň, ó, ř, š, ť, ú, ů, ý, ž

The library normalizes these for matching.

### Non-Latin Scripts

Names in non-Latin scripts are typically provided in transliterated form:

- **Hebrew**: Transliterated to Latin (Cohen, Levi)
- **Cyrillic**: Transliterated to Latin (Ivanov, Smirnov)
- **Greek**: Transliterated to Latin (Papadopoulos)
- **Arabic**: Transliterated to Latin (Abdullah, Mohammed)

The phonetic rules handle common transliteration variants.
