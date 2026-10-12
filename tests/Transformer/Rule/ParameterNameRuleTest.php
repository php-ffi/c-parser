<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule;

use FFI\Parser\Transformer\Rule\ParameterNameRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ParameterNameRule::class)]
final class ParameterNameRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new ParameterNameRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function rewrittenDeclarationsDataProvider(): iterable
    {
        yield 'undeclared name' => ['void h(int (a));', '(function (fn void (int a)) h)'];
        yield 'name of object' => ['void h(int (x));', '(function (fn void (int x)) h)'];
        yield 'pointer' => ['void h(int *(a));', '(function (fn void ((ptr int) a)) h)'];
        yield 'nested parentheses' => ['void h(int ((a)));', '(function (fn void (int a)) h)'];
        yield 'array' => ['void h(int (a)[3]);', '(function (fn void ((array int 3) a)) h)'];
        yield 'pointer to function' => [
            'void h(int (*(a))(void));',
            '(function (fn void ((ptr (fn int (void))) a)) h)',
        ];
        yield 'no type specifier' => ['void h(register (a));', '(function (fn void (register _ a)) h)'];
    }

    #[DataProvider('rewrittenDeclarationsDataProvider')]
    public function testFunctionIsTurnedIntoParameterName(string $code, string $expected): void
    {
        self::assertSame($expected, self::transform($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function keptDeclarationsDataProvider(): iterable
    {
        yield 'typedef name' => ['void h(int (T));'];
        yield 'several parameters' => ['void h(int (a, b));'];
        yield 'variadic function' => ['void h(int (a, ...));'];
        yield 'parameter of keyword type' => ['void h(int (int));'];
        yield 'named parameter' => ['void h(int (*a));'];
    }

    #[DataProvider('keptDeclarationsDataProvider')]
    public function testParameterIsKept(string $code): void
    {
        self::assertDeclarationIsKept($code);
    }

    public function testParameterNameKeepsItsOffset(): void
    {
        self::assertSame(
            '(function (fn void@37 (int@44 a@49@44))@43 h@42)@37',
            self::transform('void h(int (a));', withOffsets: true),
        );
    }
}
