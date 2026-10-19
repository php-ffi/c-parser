<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Parser;
use FFI\Parser\Preset;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class ComplexPartsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function expressionsDataProvider(): iterable
    {
        yield 'real part' => ['double x = __real__ z;', '(variable double x = (__real__u z))'];
        yield 'imaginary part' => ['double x = __imag__ z;', '(variable double x = (__imag__u z))'];
        yield 'short spellings' => [
            'double x = __real z + __imag z;',
            '(variable double x = (+ (__real__u z) (__imag__u z)))',
        ];
        yield 'operand of a postfix operator' => [
            'double x = __real__ z[1];',
            '(variable double x = (__real__u ([] z 1)))',
        ];
        yield 'before a multiplication' => [
            'double x = -__real__ z * 2;',
            '(variable double x = (* (-u (__real__u z)) 2))',
        ];
        yield 'operand of a cast' => [
            'double x = (double)__imag__ z;',
            '(variable double x = (cast double (__imag__u z)))',
        ];
        yield 'parenthesized variable before a subtraction' => [
            'double z, y; double x = __real__(z) - y;',
            '(variable double z) (variable double y) (variable double x = (- (__real__u (paren z)) y))',
        ];
    }

    #[DataProvider('expressionsDataProvider')]
    public function testExpressionIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, \implode(' ', \array_map(NodePrinter::print(...), self::parse($code))));
    }

    public function testPartIsRejectedAtItsKeywordWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse('double x = __imag__ z;');
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "ComplexParts" is not enabled', 11, 8],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
