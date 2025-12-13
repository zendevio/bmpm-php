<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit\Exceptions;

use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zendevio\BMPM\Exceptions\BeiderMorseException;
use Zendevio\BMPM\Exceptions\InvalidInputException;
use Zendevio\BMPM\Exceptions\RuleLoadException;

/**
 * @covers \Zendevio\BMPM\Exceptions\BeiderMorseException
 * @covers \Zendevio\BMPM\Exceptions\InvalidInputException
 * @covers \Zendevio\BMPM\Exceptions\RuleLoadException
 */
final class ExceptionsTest extends TestCase
{
    #[Test]
    public function beider_morse_exception_is_throwable(): void
    {
        $exception = new BeiderMorseException('Test error');

        self::assertInstanceOf(Exception::class, $exception);
        self::assertSame('Test error', $exception->getMessage());
    }

    #[Test]
    public function invalid_input_exception_extends_base(): void
    {
        $exception = new InvalidInputException('Invalid input');

        self::assertInstanceOf(BeiderMorseException::class, $exception);
    }

    #[Test]
    public function invalid_input_exception_empty_input(): void
    {
        $exception = InvalidInputException::emptyInput();

        self::assertInstanceOf(InvalidInputException::class, $exception);
        self::assertStringContainsString('empty', strtolower($exception->getMessage()));
    }

    #[Test]
    public function invalid_input_exception_invalid_encoding(): void
    {
        $exception = InvalidInputException::invalidEncoding('bad\x80string');

        self::assertInstanceOf(InvalidInputException::class, $exception);
        self::assertStringContainsString('UTF-8', $exception->getMessage());
    }

    #[Test]
    public function invalid_input_exception_input_too_long(): void
    {
        $exception = InvalidInputException::inputTooLong(1000, 500);

        self::assertInstanceOf(InvalidInputException::class, $exception);
        self::assertStringContainsString('1000', $exception->getMessage());
        self::assertStringContainsString('500', $exception->getMessage());
    }

    #[Test]
    public function rule_load_exception_extends_base(): void
    {
        $exception = new RuleLoadException('Rule error');

        self::assertInstanceOf(BeiderMorseException::class, $exception);
    }

    #[Test]
    public function rule_load_exception_file_not_found(): void
    {
        $exception = RuleLoadException::fileNotFound('/path/to/missing/file.json');

        self::assertInstanceOf(RuleLoadException::class, $exception);
        self::assertStringContainsString('/path/to/missing/file.json', $exception->getMessage());
    }

    #[Test]
    public function rule_load_exception_invalid_json(): void
    {
        $exception = RuleLoadException::invalidJson('/path/to/file.json', 'Syntax error');

        self::assertInstanceOf(RuleLoadException::class, $exception);
        self::assertStringContainsString('JSON', $exception->getMessage());
        self::assertStringContainsString('Syntax error', $exception->getMessage());
    }

    #[Test]
    public function rule_load_exception_invalid_rule_format(): void
    {
        $exception = RuleLoadException::invalidRuleFormat('/path/to/file.json', 'Missing pattern');

        self::assertInstanceOf(RuleLoadException::class, $exception);
        self::assertStringContainsString('Missing pattern', $exception->getMessage());
    }

    #[Test]
    public function rule_load_exception_missing_required_field(): void
    {
        $exception = RuleLoadException::missingRequiredField('rules', '/path/to/file.json');

        self::assertInstanceOf(RuleLoadException::class, $exception);
        self::assertStringContainsString('rules', $exception->getMessage());
    }
}
