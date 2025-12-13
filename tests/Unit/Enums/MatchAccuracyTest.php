<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit\Enums;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zendevio\BMPM\Enums\MatchAccuracy;

#[CoversClass(MatchAccuracy::class)]
final class MatchAccuracyTest extends TestCase
{
    #[Test]
    public function it_has_correct_values(): void
    {
        self::assertSame('approx', MatchAccuracy::Approximate->value);
        self::assertSame('exact', MatchAccuracy::Exact->value);
    }

    #[Test]
    public function it_returns_labels(): void
    {
        self::assertSame('Approximate', MatchAccuracy::Approximate->label());
        self::assertSame('Exact', MatchAccuracy::Exact->label());
    }

    #[Test]
    public function it_returns_descriptions(): void
    {
        $approxDesc = MatchAccuracy::Approximate->description();
        $exactDesc = MatchAccuracy::Exact->description();

        self::assertStringContainsString('more', strtolower($approxDesc));
        self::assertStringContainsString('fewer', strtolower($exactDesc));
    }

    #[Test]
    public function it_checks_approximate(): void
    {
        self::assertTrue(MatchAccuracy::Approximate->isApproximate());
        self::assertFalse(MatchAccuracy::Exact->isApproximate());
    }

    #[Test]
    public function it_checks_exact(): void
    {
        self::assertTrue(MatchAccuracy::Exact->isExact());
        self::assertFalse(MatchAccuracy::Approximate->isExact());
    }

    #[Test]
    public function it_creates_from_string(): void
    {
        self::assertSame(MatchAccuracy::Approximate, MatchAccuracy::fromString('approx'));
        self::assertSame(MatchAccuracy::Approximate, MatchAccuracy::fromString('approximate'));
        self::assertSame(MatchAccuracy::Approximate, MatchAccuracy::fromString('APPROX'));
        self::assertSame(MatchAccuracy::Exact, MatchAccuracy::fromString('exact'));
        self::assertSame(MatchAccuracy::Exact, MatchAccuracy::fromString('EXACT'));
    }

    #[Test]
    public function it_defaults_to_approximate_for_unknown_string(): void
    {
        self::assertSame(MatchAccuracy::Approximate, MatchAccuracy::fromString('unknown'));
        self::assertSame(MatchAccuracy::Approximate, MatchAccuracy::fromString(''));
    }
}
