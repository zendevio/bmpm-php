# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-12-13

### Added

- Initial release of the modern PHP 8.4+ BMPM implementation
- Complete Beider-Morse Phonetic Matching algorithm
- Support for all three name types: Generic, Ashkenazic, Sephardic
- 20 language support in Generic mode
- Dual matching accuracy modes: Exact and Approximate
- Automatic language detection from spelling patterns
- Daitch-Mokotoff Soundex implementation
- Fluent, immutable API design with `BeiderMorse` facade
- Type-safe enums for `Language`, `NameType`, and `MatchAccuracy`
- JSON-based rule files converted from original BMPM 3.15
- Comprehensive documentation

### Quality Assurance

- 400 tests with 859 assertions
- 97.51% code coverage with PCOV
- 81% MSI (Mutation Score Indicator) via Infection
- PHPStan level max compliance
- PHP-CS-Fixer with PER-CS2.0 standard
- Rector automated refactoring

### CI/CD

- GitHub Actions workflow with 5 parallel jobs
- Matrix testing: PHP 8.4
- Dependency version testing (prefer-lowest, prefer-stable)
- Automated code coverage reporting to Codecov
- Mutation testing with MSI threshold enforcement
- Security audit via Composer

### Technical Details

- Based on BMPM version 3.15 algorithm
- Rule files converted from PHP arrays to JSON format
- Full UTF-8 support with mbstring
- Proper exception hierarchy for error handling
- Caching layer for rule sets
- Batch encoding support
- Readonly classes for immutability

### Dependencies

- PHP ^8.4
- ext-mbstring
- ext-intl
- ext-json

## Algorithm Attribution

The Beider-Morse Phonetic Matching algorithm was developed by:

- **Alexander Beider** - Linguist and lexicographer
- **Stephen P. Morse** - Creator of the One-Step webpages

Original algorithm documentation: https://stevemorse.org/phoneticinfo.htm

This PHP implementation is a modern rewrite based on the BMPM 3.15 PHP source code.

[Unreleased]: https://github.com/zendevio/bmpm/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/zendevio/bmpm/releases/tag/v1.0.0
