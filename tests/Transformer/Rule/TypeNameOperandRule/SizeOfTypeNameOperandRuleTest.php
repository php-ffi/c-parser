<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule\TypeNameOperandRule;

use FFI\Parser\Tests\Transformer\Rule\RuleTestCase;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule;
use FFI\Parser\Transformer\Rule\TypeNameOperandRule\SizeOfTypeNameOperandRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(SizeOfTypeNameOperandRule::class)]
#[CoversClass(TypeNameOperandRule::class)]
final class SizeOfTypeNameOperandRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new SizeOfTypeNameOperandRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function rewrittenDeclarationsDataProvider(): iterable
    {
        yield 'array' => ['int r = sizeof(T[3]);', '(variable int r = (sizeof (array T 3)))'];
        yield 'array of arrays' => ['int r = sizeof(T[3][4]);', '(variable int r = (sizeof (array (array T 4) 3)))'];
        yield 'function' => ['int r = sizeof(T());', '(variable int r = (sizeof (fn T ())))'];
        yield 'function with parameters' => [
            'int r = sizeof(T(U, U[2]));',
            '(variable int r = (sizeof (fn T (U, (array U 2)))))',
        ];
        yield 'function returning pointer' => ['int r = sizeof(T *(U));', '(variable int r = (sizeof (fn (ptr T) (U))))'];
        yield 'function returning pointer to pointer' => [
            'int r = sizeof(T **(U, U));',
            '(variable int r = (sizeof (fn (ptr (ptr T)) (U, U))))',
        ];
        yield 'function returning array of pointers' => [
            'int r = sizeof(T *(U)[2]);',
            '(variable int r = (sizeof (fn (array (ptr T) 2) (U))))',
        ];
    }

    #[DataProvider('rewrittenDeclarationsDataProvider')]
    public function testExpressionIsTurnedIntoTypeName(string $code, string $expected): void
    {
        self::assertSame($expected, self::transform($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function keptDeclarationsDataProvider(): iterable
    {
        yield 'subscript of object' => ['int r = sizeof(x[3]);'];
        yield 'subscript of unknown name' => ['int r = sizeof(W[3]);'];
        yield 'parameter that is not a type' => ['int r = sizeof(T(x));'];
        yield 'product' => ['int r = sizeof(T * x);'];
        yield 'operand without parentheses' => ['int r = sizeof x;'];
        yield 'subscript of parenthesized operand' => ['int r = sizeof(x)[0];'];
        yield 'parenthesized typedef name' => ['int r = sizeof((T));'];
    }

    #[DataProvider('keptDeclarationsDataProvider')]
    public function testExpressionIsKept(string $code): void
    {
        self::assertDeclarationIsKept($code);
    }

    public function testTypeIsGivenTheOffsetOfTheNodeAfterItsToken(): void
    {
        self::assertSame(
            '(variable int@37 r@41 = (sizeof (fn (ptr T@52)@56 (U@57@57))@56)@45)@37',
            self::transform('int r = sizeof(T * (U));', withOffsets: true),
        );
    }
}
