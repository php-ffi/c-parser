<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class KeywordIdentifiersTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function namesDataProvider(): iterable
    {
        yield 'variable' => ['int bool;', ['(variable int bool)']];
        yield 'typedef name' => ['typedef unsigned char bool;', ['(typedef typedef (unsigned char) bool)']];
        yield 'tag' => ['struct bool { int a; };', ['(tag struct bool { int a })']];
        yield 'enumerators' => ['enum { false, true, nullptr };', ['(tag enum _ { false, true, nullptr })']];
        yield 'parameters' => [
            'int f(int alignas, int alignof, int typeof);',
            ['(function (fn int (int alignas, int alignof, int typeof)) f)'],
        ];
        yield 'member' => ['struct S { int static_assert; };', ['(tag struct S { int static_assert })']];
        yield 'keywords of the types' => [
            'int _Generic, _BitInt, _Decimal32, _Float16;',
            ['(variable int _Generic)', '(variable int _BitInt)', '(variable int _Decimal32)', '(variable int _Float16)'],
        ];
        yield 'keywords of the extensions' => ['int __asm, __typeof__;', ['(variable int __asm)', '(variable int __typeof__)']];
        yield 'enumerators of the specifiers' => [
            'enum E { constexpr, thread_local, _Imaginary, __forceinline, __ptr64, __thread };',
            ['(tag enum E { constexpr, thread_local, _Imaginary, __forceinline, __ptr64, __thread })'],
        ];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('namesDataProvider')]
    public function testKeywordIsName(string $code, array $expected): void
    {
        self::assertSame($expected, \array_map(NodePrinter::print(...), self::parse($code)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function keywordsDataProvider(): iterable
    {
        yield 'constant' => ['int x = true;', ['(variable int x = true)']];
        yield 'type specifier' => ['bool b = false;', ['(variable bool b = false)']];
        yield 'alignment specifier' => ['int alignas(8) x;', ['(variable alignas(8) int x)']];
        yield 'calling convention' => ['int __stdcall f(void);', ['(function (fn int (void) __stdcall) f)']];
        yield 'calling convention of an unnamed parameter' => [
            'void g(int * __cdecl);',
            ['(function (fn void ((attr __cdecl (ptr int)))) g)'],
        ];
        yield 'calling convention after enum' => ['enum __cdecl E { A };', ['(tag enum __cdecl E { A })']];
        yield 'pointer modifier' => ['int * __ptr64 p;', ['(variable (ptr int __ptr64) p)']];
        yield 'parenthesized type keyword of a parameter' => ['void f(int (bool));', ['(function (fn void ((fn int (bool)))) f)']];
        yield 'parenthesized typeof of a parameter' => [
            'void f(int (typeof(int)));',
            ['(function (fn void ((fn int (typeof(int))))) f)'],
        ];
        yield 'parenthesized interchange type of a parameter' => [
            'void f(int (_Float16));',
            ['(function (fn void ((fn int (_Float16)))) f)'],
        ];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('keywordsDataProvider')]
    public function testKeywordKeepsItsMeaning(string $code, array $expected): void
    {
        self::assertSame($expected, \array_map(NodePrinter::print(...), self::parse($code)));
    }
}
