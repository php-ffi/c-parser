<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Parser;
use FFI\Parser\Preset;
use FFI\Parser\Preset\Feature;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class TrailingCallingConventionsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function placesDataProvider(): iterable
    {
        yield 'after a function declarator' => ['int f(void) __cdecl;', ['(function (fn int (void) __cdecl) f)']];
        yield 'after a typedef name of a function' => [
            'typedef int F(void) __stdcall;',
            ['(typedef typedef (fn int (void) __stdcall) F)'],
        ];
        yield 'after a pointer to a function' => [
            'void (*f)(void) __stdcall;',
            ['(variable (ptr (fn void (void) __stdcall)) f)'],
        ];
        yield 'after an assembler label' => [
            'int f(void) __asm__("x") __cdecl;',
            ['(function (fn int (void) __cdecl) f __asm__("x"))'],
        ];
        yield 'among the GCC attributes' => [
            'int f(void) __attribute__((a)) __cdecl __attribute__((b));',
            ['(function (fn int (void) __cdecl) f __attribute__((a)) __attribute__((b)))'],
        ];
        yield 'after the attributes of the function' => [
            'int f(void) [[deprecated]] __cdecl;',
            ['(function (fn int (void) [[deprecated]] __cdecl) f)'],
        ];
        yield 'separated by the GCC attributes' => [
            'int f(void) __cdecl __attribute__((a)) __stdcall;',
            ['(function (fn int (void) __cdecl __stdcall) f __attribute__((a)))'],
        ];
        yield 'second declarator of the list' => [
            'int x, y __cdecl;',
            ['(variable int x)', '(variable (attr __cdecl int) y)'],
        ];
        yield 'after struct' => ['struct __cdecl S { int a; };', ['(tag struct __cdecl S { int a })']];
        yield 'after enum' => ['enum __cdecl E { A };', ['(tag enum __cdecl E { A })']];
        yield 'after enum without a body' => ['enum __cdecl E x;', ['(variable enum __cdecl E x)']];
        yield 'after a member' => [
            'struct S { int (*f)(void) __cdecl; };',
            ['(tag struct S { (ptr (fn int (void) __cdecl)) f })'],
        ];
        yield 'after the width of a bit-field' => [
            'struct S { int a : 3 __cdecl; };',
            ['(tag struct S { (attr __cdecl int) a:3 })'],
        ];
        yield 'in the brackets of an array' => [
            'void f(int a[__cdecl 3]);',
            ['(function (fn void ((array int 3 __cdecl) a)) f)'],
        ];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('placesDataProvider')]
    public function testConventionIsParsed(string $code, array $expected): void
    {
        self::assertSame($expected, \array_map(NodePrinter::print(...), self::parse($code)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, non-empty-string}>
     */
    public static function positionsDataProvider(): iterable
    {
        yield 'after a declarator' => ['int f(void) __cdecl __stdcall;', 12, '__cdecl __stdcall'];
        yield 'after struct' => ['struct __cdecl S;', 7, '__cdecl'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('positionsDataProvider')]
    public function testConventionIsRejectedWithTheCallingConventionsOnly(
        string $code,
        int $offset,
        string $fragment,
    ): void {
        try {
            (new Parser(preset: new Preset([Feature::CallingConventions])))->parse($code);
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "TrailingCallingConventions" is not enabled', $offset, $fragment],
                [$e->getMessage(), $e->offset, \substr($code, $e->offset, $e->length ?? 0)],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
