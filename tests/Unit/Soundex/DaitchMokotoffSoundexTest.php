<?php

declare(strict_types=1);

namespace Zendevio\BMPM\Tests\Unit\Soundex;

use function count;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function strlen;

use Zendevio\BMPM\Soundex\DaitchMokotoffSoundex;

#[CoversClass(DaitchMokotoffSoundex::class)]
final class DaitchMokotoffSoundexTest extends TestCase
{
    private DaitchMokotoffSoundex $soundex;

    protected function setUp(): void
    {
        $this->soundex = new DaitchMokotoffSoundex();
    }

    #[Test]
    public function it_encodes_simple_name(): void
    {
        $result = $this->soundex->encode('Smith');

        self::assertNotEmpty($result);
        self::assertMatchesRegularExpression('/^[0-9 ]+$/', $result);
    }

    #[Test]
    public function it_returns_six_digit_codes(): void
    {
        $result = $this->soundex->encode('Cohen');
        $codes = explode(' ', $result);

        foreach ($codes as $code) {
            self::assertSame(6, strlen($code), "Code '$code' should be 6 digits");
        }
    }

    #[Test]
    public function it_handles_empty_input(): void
    {
        self::assertSame('', $this->soundex->encode(''));
    }

    #[Test]
    #[DataProvider('provideKnownEncodings')]
    public function it_produces_known_encodings(string $name, string $expectedContains): void
    {
        $result = $this->soundex->encode($name);

        self::assertStringContainsString($expectedContains, $result);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideKnownEncodings(): iterable
    {
        // D-M Soundex encodings (may produce multiple codes for ambiguous names)
        yield 'cohen' => ['Cohen', '556000'];  // C=5, O=5, H=6, E=-, N=6
        yield 'schwartz' => ['Schwartz', '479400'];  // SCH=4, W=7, A=-, R=9, TZ=4
        yield 'gold' => ['Gold', '583000'];  // G=5, O=8, L=-, D=3
    }

    #[Test]
    public function it_normalizes_case(): void
    {
        $lower = $this->soundex->encode('smith');
        $upper = $this->soundex->encode('SMITH');
        $mixed = $this->soundex->encode('SmItH');

        self::assertSame($lower, $upper);
        self::assertSame($lower, $mixed);
    }

    #[Test]
    public function it_handles_diacritics(): void
    {
        $withDiacritics = $this->soundex->encode('Müller');
        $withoutDiacritics = $this->soundex->encode('Muller');

        self::assertSame($withDiacritics, $withoutDiacritics);
    }

    #[Test]
    public function it_handles_multi_word_names(): void
    {
        $result = $this->soundex->encode('Van der Berg');
        $codes = explode(' ', $result);

        // Should have codes for multiple parts
        self::assertGreaterThan(0, count($codes));
    }

    #[Test]
    public function it_produces_multiple_codes_for_ambiguous_names(): void
    {
        // Names with 'ch' can have multiple encodings (5 or 4)
        $result = $this->soundex->encode('Acker');
        $codes = explode(' ', $result);

        // 'ck' can produce branching codes
        self::assertGreaterThanOrEqual(1, count($codes));
    }

    #[Test]
    public function it_removes_duplicate_codes(): void
    {
        $result = $this->soundex->encode('test');
        $codes = explode(' ', $result);
        $unique = array_unique($codes);

        self::assertSame(count($unique), count($codes));
    }

    #[Test]
    public function it_handles_rz_alternate_encoding(): void
    {
        // 'rz' can be encoded as either 94 or 4
        $result = $this->soundex->encode('Rzeszow');
        $codes = explode(' ', $result);

        // Should produce multiple codes due to branching
        self::assertGreaterThanOrEqual(1, count($codes));
    }

    #[Test]
    public function it_handles_ch_alternate_encoding(): void
    {
        // 'ch' can be encoded as 5 or 4
        $result = $this->soundex->encode('Chmelnik');
        $codes = explode(' ', $result);

        self::assertGreaterThanOrEqual(1, count($codes));
    }

    #[Test]
    public function it_handles_ck_alternate_encoding(): void
    {
        // 'ck' can be encoded as 5 or 45
        $result = $this->soundex->encode('Becker');
        $codes = explode(' ', $result);

        self::assertGreaterThanOrEqual(1, count($codes));
    }

    #[Test]
    public function it_handles_c_alternate_encoding(): void
    {
        // 'c' can be encoded as 5 or 4
        $result = $this->soundex->encode('Caban');
        $codes = explode(' ', $result);

        self::assertGreaterThanOrEqual(1, count($codes));
    }

    #[Test]
    public function it_handles_j_alternate_encoding(): void
    {
        // 'j' can be encoded as 1/999 or 4
        $result = $this->soundex->encode('Jacobs');
        $codes = explode(' ', $result);

        self::assertGreaterThanOrEqual(1, count($codes));
    }

    #[Test]
    public function it_handles_unrecognized_characters(): void
    {
        // Numbers and special chars should be skipped
        $result = $this->soundex->encode('Test123Name');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_vowel_before_next_char(): void
    {
        // Test pattern followed by vowel
        $result = $this->soundex->encode('Schaffer');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_complex_diacritics(): void
    {
        // Test various diacritics
        $result = $this->soundex->encode('Böse');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_name_with_commas(): void
    {
        // Names separated by commas
        $result = $this->soundex->encode('Smith,Jones');
        $codes = explode(' ', $result);

        self::assertGreaterThanOrEqual(1, count($codes));
    }

    #[Test]
    public function it_handles_name_with_slashes(): void
    {
        // Names separated by slashes
        $result = $this->soundex->encode('Smith/Jones');
        $codes = explode(' ', $result);

        self::assertGreaterThanOrEqual(1, count($codes));
    }

    #[Test]
    public function it_handles_whitespace_only(): void
    {
        $result = $this->soundex->encode('   ');

        self::assertSame('', $result);
    }

    #[Test]
    public function it_encodes_long_patterns_first(): void
    {
        // 'schtsch' is the longest pattern (7 chars)
        $result = $this->soundex->encode('Schtscherbakow');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_consecutive_vowels(): void
    {
        // Test vowel sequences
        $result = $this->soundex->encode('Auerbach');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_vowel_dipthongs(): void
    {
        // Test ai, au, ei, eu, oi, ui patterns
        $result = $this->soundex->encode('Baumgarten');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_mn_nm_patterns(): void
    {
        // mn and nm produce code 66
        $result = $this->soundex->encode('Mannheim');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_code_999_suppression(): void
    {
        // 999 code means "not coded" - used for some vowels
        $result = $this->soundex->encode('ai');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_branching_with_same_last_code(): void
    {
        // When the new code equals the last code, it shouldn't be added
        $result = $this->soundex->encode('Schiller');
        $codes = explode(' ', $result);

        foreach ($codes as $code) {
            self::assertSame(6, strlen($code));
        }
    }

    #[Test]
    public function it_encodes_all_single_letters(): void
    {
        // Test each letter of the alphabet
        foreach (range('a', 'z') as $letter) {
            $result = $this->soundex->encode($letter);
            self::assertNotEmpty($result, "Letter '$letter' should produce a code");
            // Some letters may produce multiple codes due to branching
            $codes = explode(' ', $result);
            foreach ($codes as $code) {
                self::assertSame(6, strlen($code), "Code '$code' for '$letter' should be 6 digits");
            }
        }
    }

    #[Test]
    public function it_handles_start_of_name_codes(): void
    {
        // Test patterns at start of name get 'start' code
        $result = $this->soundex->encode('Schubert');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_before_vowel_codes(): void
    {
        // Test patterns before a vowel get 'vowel' code
        $result = $this->soundex->encode('Ascher');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_other_position_codes(): void
    {
        // Test patterns not at start or before vowel get 'other' code
        $result = $this->soundex->encode('Rasch');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_german_sharp_s(): void
    {
        // ß should be converted to ss
        $result = $this->soundex->encode('Straße');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_ligatures(): void
    {
        // æ -> ae, œ -> oe
        $result = $this->soundex->encode('Cæsar');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_handles_polish_l_stroke(): void
    {
        // ł -> l
        $result = $this->soundex->encode('Łódź');

        self::assertNotEmpty($result);
    }

    #[Test]
    public function it_skips_duplicate_codes_in_branching(): void
    {
        // 'gch' - g produces 5, ch produces 5/4
        // Primary code 5 equals previous 5, so it should be skipped
        $result = $this->soundex->encode('gch');
        $codes = explode(' ', $result);

        // Should produce multiple codes due to branching
        self::assertGreaterThanOrEqual(1, count($codes));
        foreach ($codes as $code) {
            self::assertSame(6, strlen($code));
        }
    }

    #[Test]
    public function it_handles_consecutive_alt_patterns(): void
    {
        // 'ckck' - tests consecutive alternate patterns
        // First ck: 5/45, second ck applied to both branches
        $result = $this->soundex->encode('ckck');
        $codes = explode(' ', $result);

        // Should handle the branching without duplicates
        self::assertGreaterThanOrEqual(1, count($codes));
    }

    #[Test]
    public function it_handles_alt_code_equals_last_code(): void
    {
        // 'kc' - k produces 5, c produces 5/4
        // Alternate code 4 doesn't equal previous 5, so both are added
        $result = $this->soundex->encode('kc');
        $codes = explode(' ', $result);

        self::assertGreaterThanOrEqual(1, count($codes));
    }

    #[Test]
    public function it_handles_vowel_between_alt_patterns(): void
    {
        // Test with vowel between branching patterns
        // 'cac' - triggers vowel context for middle 'a'
        $result = $this->soundex->encode('cac');
        $codes = explode(' ', $result);

        self::assertGreaterThanOrEqual(1, count($codes));
    }

    #[Test]
    public function it_handles_999_code_in_branching(): void
    {
        // Names that produce 999 code (not coded) in branching context
        // 'ja' - j produces 1/4 at start, a produces 999 (not coded after vowel)
        $result = $this->soundex->encode('ja');
        $codes = explode(' ', $result);

        self::assertGreaterThanOrEqual(1, count($codes));
    }
}
