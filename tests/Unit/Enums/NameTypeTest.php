<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zendevio\BMPM\Enums\NameType;

#[CoversClass(NameType::class)]
final class NameTypeTest extends TestCase
{
    #[Test]
    public function it_has_correct_values(): void
    {
        self::assertSame('gen', NameType::Generic->value);
        self::assertSame('ash', NameType::Ashkenazic->value);
        self::assertSame('sep', NameType::Sephardic->value);
    }

    #[Test]
    public function it_returns_directory_name(): void
    {
        self::assertSame('Generic', NameType::Generic->directory());
        self::assertSame('Ashkenazic', NameType::Ashkenazic->directory());
        self::assertSame('Sephardic', NameType::Sephardic->directory());
    }

    #[Test]
    public function it_returns_labels(): void
    {
        self::assertSame('Generic', NameType::Generic->label());
        self::assertSame('Ashkenazic', NameType::Ashkenazic->label());
        self::assertSame('Sephardic', NameType::Sephardic->label());
    }

    #[Test]
    public function it_returns_descriptions(): void
    {
        self::assertStringContainsString('20 languages', NameType::Generic->description());
        self::assertStringContainsString('Ashkenazic', NameType::Ashkenazic->description());
        self::assertStringContainsString('Sephardic', NameType::Sephardic->description());
    }

    #[Test]
    public function it_creates_from_string(): void
    {
        self::assertSame(NameType::Generic, NameType::fromString('gen'));
        self::assertSame(NameType::Generic, NameType::fromString('generic'));
        self::assertSame(NameType::Generic, NameType::fromString('GENERIC'));
        self::assertSame(NameType::Ashkenazic, NameType::fromString('ash'));
        self::assertSame(NameType::Ashkenazic, NameType::fromString('ashkenazic'));
        self::assertSame(NameType::Ashkenazic, NameType::fromString('ashkenazi'));
        self::assertSame(NameType::Sephardic, NameType::fromString('sep'));
        self::assertSame(NameType::Sephardic, NameType::fromString('sephardic'));
        self::assertSame(NameType::Sephardic, NameType::fromString('sephardi'));
    }

    #[Test]
    public function it_defaults_to_generic_for_unknown_string(): void
    {
        self::assertSame(NameType::Generic, NameType::fromString('unknown'));
        self::assertSame(NameType::Generic, NameType::fromString(''));
    }
}
