<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Node\Literal\IntLiteral\BaseConverter;

use FFI\Parser\Node\Literal\IntLiteral\BaseConverter\BaseConverterInterface;
use FFI\Parser\Node\Literal\IntLiteral\BaseConverter\BcMathBaseConverter;
use FFI\Parser\Node\Literal\IntLiteral\BaseConverter\GmpBaseConverter;
use FFI\Parser\Node\Literal\IntLiteral\BaseConverter\NativeBaseConverter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[CoversClass(GmpBaseConverter::class)]
#[CoversClass(BcMathBaseConverter::class)]
#[CoversClass(NativeBaseConverter::class)]
final class BaseConverterInterfaceTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<2, 36>, numeric-string}>
     */
    public static function numbersDataProvider(): iterable
    {
        yield 'zero' => ['0', 16, '0'];
        yield 'one' => ['1', 2, '1'];
        yield 'leading zeros' => ['0001', 16, '1'];
        yield 'lower case digits' => ['ff', 16, '255'];
        yield 'upper case digits' => ['FF', 16, '255'];
        yield 'octal' => ['777', 8, '511'];
        yield 'binary' => ['1010', 2, '10'];
        yield 'limb boundary minus one' => ['3B9AC9FF', 16, '999999999'];
        yield 'limb boundary' => ['3B9ACA00', 16, '1000000000'];
        yield 'limb boundary plus one' => ['3B9ACA01', 16, '1000000001'];
        yield 'zeros inside a limb' => ['DE0B6B3A7640000', 16, '1000000000000000000'];
        yield '2^63 - 1' => ['7FFFFFFFFFFFFFFF', 16, '9223372036854775807'];
        yield '2^63' => ['8000000000000000', 16, '9223372036854775808'];
        yield '2^64 - 1' => ['FFFFFFFFFFFFFFFF', 16, '18446744073709551615'];
        yield '2^64 - 1 in octal' => ['1777777777777777777777', 8, '18446744073709551615'];
        yield '2^64 - 1 in binary' => [\str_repeat('1', 64), 2, '18446744073709551615'];
        yield '2^128 - 1' => [\str_repeat('F', 32), 16, '340282366920938463463374607431768211455'];
        yield '2^400' => [
            '1' . \str_repeat('0', 100),
            16,
            '25822498780869085896559191720030118743297057928292235128306593565406'
                . '47622016841194629645353280137831435903171972747493376',
        ];

        yield '15 hexadecimal digits' => [\str_repeat('F', 15), 16, '1152921504606846975'];
        yield '16 hexadecimal digits' => [\str_repeat('F', 16), 16, '18446744073709551615'];
        yield '21 octal digits' => [\str_repeat('7', 21), 8, '9223372036854775807'];
        yield '22 octal digits' => [\str_repeat('7', 22), 8, '73786976294838206463'];
        yield '63 binary digits' => [\str_repeat('1', 63), 2, '9223372036854775807'];
        yield '64 binary digits' => [\str_repeat('1', 64), 2, '18446744073709551615'];
        yield '12 digits in base 36' => [\str_repeat('z', 12), 36, '4738381338321616895'];
        yield '13 digits in base 36' => [\str_repeat('z', 13), 36, '170581728179578208255'];
        yield 'leading zeros do not count' => [\str_repeat('0', 20) . \str_repeat('F', 15), 16, '1152921504606846975'];

        yield 'base 3' => ['2101', 3, '64'];
        yield 'base 7' => ['666', 7, '342'];
        yield 'base 10' => ['00123', 10, '123'];
        yield 'base 36 in lower case' => ['zz', 36, '1295'];
        yield 'base 36 in upper case' => ['ZZ', 36, '1295'];
        yield '36^20 - 1' => [\str_repeat('z', 20), 36, '13367494538843734067838845976575'];
    }

    #[DataProvider('numbersDataProvider')]
    #[RequiresPhpExtension('gmp')]
    public function testGmpConverterConvertsTheBase(string $digits, int $base, string $decimal): void
    {
        self::assertConverted(new GmpBaseConverter(), $digits, $base, $decimal);
    }

    #[DataProvider('numbersDataProvider')]
    #[RequiresPhpExtension('bcmath')]
    public function testBcMathConverterConvertsTheBase(string $digits, int $base, string $decimal): void
    {
        self::assertConverted(new BcMathBaseConverter(), $digits, $base, $decimal);
    }

    #[DataProvider('numbersDataProvider')]
    public function testNativeConverterConvertsTheBase(string $digits, int $base, string $decimal): void
    {
        self::assertConverted(new NativeBaseConverter(), $digits, $base, $decimal);
    }

    #[RequiresPhpExtension('bcmath')]
    public function testBcMathConverterIgnoresTheScaleSetting(): void
    {
        $scale = \bcscale(5);

        try {
            self::assertConverted(new BcMathBaseConverter(), 'FF', 16, '255');
        } finally {
            \bcscale($scale);
        }
    }

    private static function assertConverted(
        BaseConverterInterface $converter,
        string $digits,
        int $base,
        string $decimal,
    ): void {
        self::assertTrue($converter->isAvailable());
        self::assertSame($decimal, $converter->toDecimal($digits, $base));
    }
}
