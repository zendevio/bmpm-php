# Contributing to BMPM

Thank you for considering contributing to the BMPM library! This document provides guidelines and information for contributors.

## Code of Conduct

Please be respectful and constructive in all interactions. We welcome contributors of all backgrounds and experience levels.

## How to Contribute

### Reporting Bugs

1. Check if the bug has already been reported in [Issues](https://github.com/zendevio/bmpm/issues)
2. If not, create a new issue with:
   - Clear, descriptive title
   - Steps to reproduce
   - Expected vs. actual behavior
   - PHP version and environment details
   - Minimal code example if possible

### Suggesting Features

1. Check existing issues and discussions
2. Create a new issue describing:
   - The use case / problem to solve
   - Proposed solution
   - Any alternatives considered

### Submitting Changes

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Make your changes
4. Run the test suite: `composer test`
5. Run static analysis: `composer analyse`
6. Fix code style: `composer cs-fix`
7. Commit with clear messages
8. Push and create a Pull Request

## Development Setup

### Requirements

- PHP 8.4+
- Composer
- Git

### Installation

```bash
git clone https://github.com/zendevio/bmpm.git
cd bmpm
composer install
```

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage (generates coverage.xml)
composer test:coverage

# Run specific test file
./vendor/bin/phpunit tests/Unit/BeiderMorseTest.php
```

### Static Analysis

```bash
# PHPStan at max level
composer analyse
```

### Code Style

This project uses PHP-CS-Fixer with PER-CS2.0 standard.

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

### Mutation Testing

We use Infection for mutation testing. This ensures tests actually verify behavior, not just cover code.

```bash
# Run mutation tests (requires 80% MSI)
composer infection

# Current metrics: 81% MSI
```

**Important**: PRs should maintain or improve the MSI score.

### Automated Refactoring

We use Rector for automated code improvements.

```bash
# Preview refactoring changes
composer rector:dry

# Apply refactoring
composer rector
```

### Full CI Pipeline

Run all quality checks locally before submitting a PR:

```bash
# Run all checks (cs-check, analyse, test)
composer check

# Full CI pipeline (security, cs-check, analyse, test, infection)
composer ci
```

## Coding Standards

### PHP Version

- Target PHP 8.4+ features
- Use strict typing: `declare(strict_types=1)`
- Use enums for finite sets of values
- Use readonly properties where appropriate
- Use constructor property promotion

### Style Guidelines

- Follow PER-CS2.0 (PHP-CS-Fixer configured)
- Use meaningful variable and method names
- Keep methods focused and small
- Prefer composition over inheritance

### Documentation

- Add PHPDoc blocks to all public methods
- Include `@param`, `@return`, and `@throws` tags
- Write clear descriptions, not just type repetition

```php
/**
 * Encode a name to its phonetic representation.
 *
 * @param string $name The name to encode (will be normalized to lowercase UTF-8)
 *
 * @return string Phonetic encoding with alternatives in (a|b) format
 *
 * @throws InvalidInputException If the input is empty or invalid
 */
public function encode(string $name): string
```

### Testing

- Write tests for all new functionality
- Use PHPUnit attributes (`#[Test]`, `#[CoversClass]`)
- Test edge cases and error conditions
- Aim for high coverage of new code (target: 95%+)
- Ensure tests kill mutations (target MSI: 80%+)

```php
#[Test]
public function it_encodes_german_name(): void
{
    $encoder = new BeiderMorse();
    $result = $encoder->encode('Müller');

    self::assertNotEmpty($result);
    self::assertStringContainsString('milr', $result);
}
```

## Project Structure

```
src/
├── BeiderMorse.php          # Main facade
├── Contracts/               # Interfaces
├── Engine/                  # Core processing
├── Enums/                   # Type enums
├── Exceptions/              # Custom exceptions
├── Rules/                   # Rule loading/management
│   └── Data/                # JSON rule files
├── Soundex/                 # D-M Soundex
└── Util/                    # Utilities

tests/
├── Unit/                    # Unit tests
├── Integration/             # Integration tests
├── Regression/              # Regression tests
└── Fixtures/                # Test fixtures
```

## Pull Request Process

1. Ensure all tests pass (`composer test`)
2. Ensure code style is correct (`composer cs-check`)
3. Ensure static analysis passes (`composer analyse`)
4. Ensure mutation score is maintained (`composer infection`)
5. Update documentation if needed
6. Update CHANGELOG.md for notable changes
7. Request review from maintainers

### PR Title Format

- `feat: Add new feature`
- `fix: Fix bug in X`
- `docs: Update documentation`
- `refactor: Refactor X component`
- `test: Add tests for X`
- `chore: Update dependencies`

## Rule File Contributions

If contributing changes to phonetic rules:

1. Rules are in `src/Rules/Data/{Generic,Ashkenazic,Sephardic}/`
2. Format is JSON with specific structure (see docs)
3. Test thoroughly - rule changes affect many names
4. Document the linguistic reasoning
5. Original BMPM rules are in `resources/bmpm-php-3.15/` for reference

## Questions?

- Open a [Discussion](https://github.com/zendevio/bmpm/discussions)
- Check existing [Issues](https://github.com/zendevio/bmpm/issues)

## License

By contributing, you agree that your contributions will be licensed under the GPL-3.0 License.

Thank you for contributing!
