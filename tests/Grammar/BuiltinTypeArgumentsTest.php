<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class BuiltinTypeArgumentsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function callsDataProvider(): iterable
    {
        yield 'offsetof with a member designator' => [
            'int x = __builtin_offsetof(struct S, a.b[1]);',
            '(variable int x = (__builtin_offsetof struct S ([] (. a b) 1)))',
        ];
        yield 'va_arg' => ['int x = __builtin_va_arg(ap, int);', '(variable int x = (__builtin_va_arg ap int))'];
        yield 'two types' => [
            'int x = __builtin_types_compatible_p(int, long);',
            '(variable int x = (__builtin_types_compatible_p int (long _)))',
        ];
        yield 'typedef names' => [
            'typedef int T; int x = __builtin_types_compatible_p(T, T[2]);',
            '(variable int x = (__builtin_types_compatible_p T (array T 2)))',
        ];
        yield 'pointer to a typedef name' => [
            'typedef int T; int x = __builtin_va_arg(ap, T *);',
            '(variable int x = (__builtin_va_arg ap (ptr T)))',
        ];
        yield 'operand of a binary operator' => [
            'int x = __builtin_offsetof(struct S, a) + 1;',
            '(variable int x = (+ (__builtin_offsetof struct S a) 1))',
        ];
        yield 'expressions only' => ['int x = __builtin_expect(y, 1);', '(variable int x = (call __builtin_expect y 1))'];
        yield 'no arguments' => ['int x = __builtin_foo();', '(variable int x = (call __builtin_foo))'];
        yield 'postfix operator after the call' => [
            'int x = __builtin_bar(1)[0];',
            '(variable int x = ([] (call __builtin_bar 1) 0))',
        ];
        yield 'declaration of a built-in function' => ['int __builtin_baz(int);', '(function (fn int (int)) __builtin_baz)'];
        yield 'type of the built-in functions' => ['__builtin_va_list ap;', '(variable __builtin_va_list ap)'];
        yield 'type of the built-in functions before a parenthesized declarator' => [
            '__builtin_va_list (*p);',
            '(variable (ptr __builtin_va_list) p)',
        ];
        yield 'parameter of the type of the built-in functions' => [
            'void f(__builtin_va_list (ap));',
            '(function (fn void (__builtin_va_list ap)) f)',
        ];
        yield 'operand of a cast to a typedef name' => [
            'typedef int T; int x = (T)__builtin_expect(y, 1);',
            '(variable int x = (cast T (call __builtin_expect y 1)))',
        ];
        yield 'member named as a built-in function' => [
            'int x = s.__builtin_cb(1) + p->__builtin_cb(2);',
            '(variable int x = (+ (call (. s __builtin_cb) 1) (call (-> p __builtin_cb) 2)))',
        ];
        yield 'operand of sizeof' => [
            'int x = sizeof(__builtin_foo());',
            '(variable int x = (sizeof (paren (call __builtin_foo))))',
        ];
    }

    #[DataProvider('callsDataProvider')]
    public function testCallIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(\array_last(self::parse($code))));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, non-empty-string}>
     */
    public static function invalidCallsDataProvider(): iterable
    {
        yield 'type name of another function' => ['int x = f(int);', 10, 'int'];
        yield 'unclosed arguments' => ['int x = __builtin_va_arg(ap, int;', 32, ';'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidCallsDataProvider')]
    public function testInvalidCallIsRejected(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }
}
