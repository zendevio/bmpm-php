# API Reference

Complete API documentation for the BMPM library.

## Table of Contents

- [BeiderMorse](#beidermorse) - Main facade class
- [Enums](#enums) - Type-safe configuration options
- [Engine Classes](#engine-classes) - Core processing components
- [Utility Classes](#utility-classes) - Helper utilities
- [Exceptions](#exceptions) - Error handling

---

## BeiderMorse

The main facade class providing a simple interface to all BMPM functionality.

**Namespace:** `Zendevio\BMPM`

### Constructor

```php
public function __construct()
```

Creates a new BeiderMorse instance with default settings:
- Name Type: Generic
- Accuracy: Approximate
- Language: Auto-detect

### Static Factory

```php
public static function create(): self
```

Fluent factory method for chained configuration.

**Example:**
```php
$encoder = BeiderMorse::create()
    ->withNameType(NameType::Ashkenazic)
    ->withAccuracy(MatchAccuracy::Exact);
```

### Configuration Methods

All configuration methods return a new instance (immutable pattern).

#### withNameType

```php
public function withNameType(NameType $nameType): self
```

Set the name type variant for phonetic encoding.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$nameType` | `NameType` | Generic, Ashkenazic, or Sephardic |

#### withAccuracy

```php
public function withAccuracy(MatchAccuracy $accuracy): self
```

Set the matching accuracy mode.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$accuracy` | `MatchAccuracy` | Exact or Approximate |

#### withLanguages

```php
public function withLanguages(Language ...$languages): self
```

Restrict encoding to specific language(s).

| Parameter | Type | Description |
|-----------|------|-------------|
| `$languages` | `Language...` | One or more Language enum values |

**Example:**
```php
$encoder = BeiderMorse::create()
    ->withLanguages(Language::German, Language::Polish, Language::Russian);
```

#### withLanguageMask

```php
public function withLanguageMask(int $mask): self
```

Set language restriction using a bitmask directly.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$mask` | `int` | Combined language bitmask |

**Example:**
```php
$mask = Language::German->value | Language::English->value;
$encoder = BeiderMorse::create()->withLanguageMask($mask);
```

#### withAutoLanguageDetection

```php
public function withAutoLanguageDetection(): self
```

Clear language restrictions and enable automatic detection.

#### withDataPath

```php
public function withDataPath(string $path): self
```

Set a custom path for rule data files.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | `string` | Absolute path to rules directory |

### Encoding Methods

#### encode

```php
public function encode(string $name): string
```

Encode a name to its phonetic representation.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | The name to encode |

**Returns:** `string` - Phonetic encoding with alternatives in `(a|b|c)` format

**Example:**
```php
$phonetic = $encoder->encode('Schwarzenegger');
// "(Svarcenegr|Svarceneger|...)"
```

#### encodeToArray

```php
public function encodeToArray(string $name): array
```

Encode a name and return all alternatives as an array.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | The name to encode |

**Returns:** `array<string>` - Array of phonetic alternatives

**Example:**
```php
$alternatives = $encoder->encodeToArray('Mueller');
// ['milr', 'mylr', 'miler', ...]
```

#### encodeBatch

```php
public function encodeBatch(array $names): array
```

Encode multiple names in batch.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$names` | `array<string>` | Array of names to encode |

**Returns:** `array<string, string>` - Associative array of name => phonetic

### Matching Methods

#### matches

```php
public function matches(string $name1, string $name2): bool
```

Check if two names might match phonetically.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name1` | `string` | First name |
| `$name2` | `string` | Second name |

**Returns:** `bool` - True if any phonetic alternatives match

#### similarity

```php
public function similarity(string $name1, string $name2): float
```

Get the similarity score between two names using Jaccard index.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name1` | `string` | First name |
| `$name2` | `string` | Second name |

**Returns:** `float` - Score between 0.0 (no match) and 1.0 (identical)

### Language Detection Methods

#### detectLanguages

```php
public function detectLanguages(string $name): array
```

Detect all possible language(s) of a name.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | The name to analyze |

**Returns:** `array<Language>` - Array of detected languages

#### detectPrimaryLanguage

```php
public function detectPrimaryLanguage(string $name): Language
```

Detect the primary (most likely) language of a name.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | The name to analyze |

**Returns:** `Language` - The most likely language

### Soundex Method

#### soundex

```php
public function soundex(string $name): string
```

Get Daitch-Mokotoff Soundex encoding.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | The name to encode |

**Returns:** `string` - Space-separated 6-digit D-M Soundex codes

### Getter Methods

#### getNameType

```php
public function getNameType(): NameType
```

Get the current name type setting.

#### getAccuracy

```php
public function getAccuracy(): MatchAccuracy
```

Get the current accuracy setting.

#### getLanguageMask

```php
public function getLanguageMask(): ?int
```

Get the current language mask (null if auto-detect).

#### getAvailableLanguages

```php
public function getAvailableLanguages(): array
```

Get all languages available for the current name type.

**Returns:** `array<Language>`

---

## Enums

### NameType

**Namespace:** `Zendevio\BMPM\Enums`

```php
enum NameType: string
{
    case Generic = 'gen';
    case Ashkenazic = 'ash';
    case Sephardic = 'sep';
}
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `directory()` | `string` | Directory name for rule files |
| `label()` | `string` | Human-readable label |
| `description()` | `string` | Detailed description |
| `fromString(string $value)` | `self` | Create from string (case-insensitive) |

### MatchAccuracy

**Namespace:** `Zendevio\BMPM\Enums`

```php
enum MatchAccuracy: string
{
    case Exact = 'exact';
    case Approximate = 'approx';
}
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `label()` | `string` | Human-readable label |
| `description()` | `string` | Detailed description |
| `fromString(string $value)` | `self` | Create from string |
| `isApproximate()` | `bool` | Check if Approximate mode |
| `isExact()` | `bool` | Check if Exact mode |

### Language

**Namespace:** `Zendevio\BMPM\Enums`

```php
enum Language: int
{
    case Any = 1;
    case Arabic = 2;
    case Cyrillic = 4;
    case Czech = 8;
    case Dutch = 16;
    case English = 32;
    case French = 64;
    case German = 128;
    case Greek = 256;
    case GreekLatin = 512;
    case Hebrew = 1024;
    case Hungarian = 2048;
    case Italian = 4096;
    case Latvian = 8192;
    case Polish = 16384;
    case Portuguese = 32768;
    case Romanian = 65536;
    case Russian = 131072;
    case Spanish = 262144;
    case Turkish = 524288;
}
```

#### Static Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `genericLanguages()` | `array<self>` | Languages for Generic mode |
| `ashkenazicLanguages()` | `array<self>` | Languages for Ashkenazic mode |
| `sephardicLanguages()` | `array<self>` | Languages for Sephardic mode |
| `forNameType(NameType $type)` | `array<self>` | Languages for specified name type |
| `fromString(string $name)` | `?self` | Create from name (case-insensitive) |
| `fromMask(int $mask)` | `array<self>` | Get languages from bitmask |
| `combineMask(array $langs)` | `int` | Combine languages to bitmask |

#### Instance Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `ruleName()` | `string` | Lowercase name for rule files |
| `label()` | `string` | Human-readable label |
| `index(NameType $type)` | `int` | Index position for name type |
| `isInMask(int $mask)` | `bool` | Check if included in mask |

---

## Engine Classes

### PhoneticEngine

**Namespace:** `Zendevio\BMPM\Engine`
**Implements:** `PhoneticEncoderInterface`

The core phonetic encoding engine implementing the Beider-Morse algorithm.

```php
public function __construct(
    RuleLoaderInterface $ruleLoader,
    LanguageDetectorInterface $languageDetector,
)
```

#### Methods

| Method | Description |
|--------|-------------|
| `encode(...)` | Encode a single name |
| `encodeToArray(...)` | Encode and return array |
| `encodeBatch(...)` | Encode multiple names |
| `clearCache()` | Clear internal rule caches |

### LanguageDetector

**Namespace:** `Zendevio\BMPM\Engine`
**Implements:** `LanguageDetectorInterface`

Detects language(s) of a name based on spelling patterns.

```php
public function __construct(RuleLoaderInterface $ruleLoader)
```

#### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `detect(string $name, NameType $type)` | `int` | Bitmask of detected languages |
| `detectLanguages(string $name, NameType $type)` | `array<Language>` | Array of detected languages |
| `detectPrimary(string $name, NameType $type)` | `Language` | Primary detected language |
| `clearCache()` | `void` | Clear internal caches |

---

## Utility Classes

### PhoneticExpander

**Namespace:** `Zendevio\BMPM\Util`

Expands phonetic alternates in parenthesized notation.

#### Static Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `expand(string $phonetic)` | `array<string>` | Expand to array of alternatives |
| `collapse(array $alternatives)` | `string` | Convert array back to parenthesized |
| `removeDuplicates(string $phonetic)` | `string` | Remove duplicate alternatives |
| `hasAlternates(string $phonetic)` | `bool` | Check if has alternatives |
| `countAlternatives(string $phonetic)` | `int` | Count number of alternatives |

### StringHelper

**Namespace:** `Zendevio\BMPM\Util`

UTF-8 safe string manipulation utilities.

#### Static Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `normalize(string $input)` | `string` | Normalize to UTF-8 lowercase |
| `toUtf8(string $input)` | `string` | Convert to UTF-8 |
| `isAscii(string $input)` | `bool` | Check if ASCII only |
| `substring(string $input, int $start, ?int $length)` | `string` | UTF-8 safe substring |
| `length(string $input)` | `int` | UTF-8 string length |

### DaitchMokotoffSoundex

**Namespace:** `Zendevio\BMPM\Soundex`

Daitch-Mokotoff Soundex implementation for Slavic/Yiddish names.

```php
public function encode(string $name): string
```

Returns space-separated 6-digit codes.

---

## Exceptions

All exceptions extend `BeiderMorseException`.

### BeiderMorseException

**Namespace:** `Zendevio\BMPM\Exceptions`

Base exception class for all BMPM errors.

### InvalidInputException

Thrown when input validation fails.

#### Factory Methods

| Method | Description |
|--------|-------------|
| `emptyInput()` | Input is empty |
| `invalidEncoding(string $input)` | Input is not valid UTF-8 |
| `inputTooLong(int $length, int $max)` | Input exceeds max length |

### RuleLoadException

Thrown when rule loading fails.

#### Factory Methods

| Method | Description |
|--------|-------------|
| `fileNotFound(string $path)` | Rule file not found |
| `invalidJson(string $path, string $error)` | Invalid JSON in rule file |
| `invalidRuleFormat(string $path, string $error)` | Invalid rule format |
| `missingRequiredField(string $field, string $path)` | Missing required field |

---

## Contracts (Interfaces)

### PhoneticEncoderInterface

```php
interface PhoneticEncoderInterface
{
    public function encode(string $input, ...): string;
    public function encodeToArray(string $input, ...): array;
    public function encodeBatch(array $inputs, ...): array;
}
```

### LanguageDetectorInterface

```php
interface LanguageDetectorInterface
{
    public function detect(string $name, NameType $nameType): int;
    public function detectLanguages(string $name, NameType $nameType): array;
    public function detectPrimary(string $name, NameType $nameType): Language;
}
```

### RuleLoaderInterface

```php
interface RuleLoaderInterface
{
    public function loadRules(Language $language, NameType $nameType): RuleSet;
    public function loadFinalRules(Language $language, NameType $nameType, MatchAccuracy $accuracy): RuleSet;
    public function loadCommonRules(NameType $nameType, MatchAccuracy $accuracy): RuleSet;
    public function loadLanguageRules(NameType $nameType): array;
    public function clearCache(): void;
}
```
