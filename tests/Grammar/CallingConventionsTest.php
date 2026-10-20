<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class CallingConventionsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function placesDataProvider(): iterable
    {
        yield 'before the name of a function' => ['int __stdcall f(void);', ['(function (fn int (void) __stdcall) f)']];
        yield 'after a typedef name' => [
            'typedef int T; T __cdecl f(void);',
            ['(typedef typedef int T)', '(function (fn T (void) __cdecl) f)'],
        ];
        yield 'after a pointer before the name of a function' => [
            'void * __stdcall GetProcAddress(void *h, const char *n);',
            ['(function (fn (ptr void) ((ptr void) h, (ptr [const char]) n) __stdcall) GetProcAddress)'],
        ];
        yield 'second declarator of the list' => [
            'int __cdecl a(void), __stdcall b(void);',
            ['(function (fn int (void) __cdecl) a)', '(function (fn int (void) __stdcall) b)'],
        ];
        yield 'parenthesized pointer to a function' => [
            'typedef int (__stdcall *FP)(int);',
            ['(typedef typedef (ptr (fn int (int) __stdcall)) FP)'],
        ];
        yield 'after a pointer before the name of a pointer to a function' => [
            'void (* const __stdcall f)(int);',
            ['(variable [const (ptr (fn void (int) __stdcall))] f)'],
        ];
        yield 'array of pointers to functions' => [
            'int (__stdcall *f[3])(void);',
            ['(variable (array (ptr (fn int (void) __stdcall)) 3) f)'],
        ];
        yield 'before a parenthesized name' => ['int __stdcall (f)(void);', ['(function (fn int (void) __stdcall) f)']];
        yield 'before a parenthesized pointer' => [
            'int __stdcall (*f)(void);',
            ['(variable (ptr (fn int (void) __stdcall)) f)'],
        ];
        yield 'parenthesized name of a function' => ['int (__stdcall f)(int);', ['(function (fn int (int) __stdcall) f)']];
        yield 'function returning a pointer to a function' => [
            'void (__cdecl * __cdecl signal(int, void (__cdecl *)(int)))(int);',
            [
                '(function (fn (ptr (fn void (int) __cdecl)) (int, (ptr (fn void (int) __cdecl))) __cdecl)'
                . ' signal)',
            ],
        ];
        yield 'member' => [
            'struct S { void (__stdcall *cb)(int); };',
            ['(tag struct S { (ptr (fn void (int) __stdcall)) cb })'],
        ];
        yield 'before the attributes of the function' => [
            'int __stdcall f(void) [[deprecated]];',
            ['(function (fn int (void) __stdcall [[deprecated]]) f)'],
        ];
        yield 'several conventions' => [
            'void __stdcall __cdecl f(void);',
            ['(function (fn void (void) __stdcall __cdecl) f)'],
        ];
        yield 'with the GCC attributes of a parenthesized declarator' => [
            'void (__attribute__((a)) __stdcall *f)(void);',
            ['(variable (ptr (fn void (void) __attribute__((a)) __stdcall)) f)'],
        ];
        yield 'function definition' => [
            'int __fastcall f(void) { return 0; }',
            ['(definition (fn int (void) __fastcall) f { return 0; })'],
        ];
        yield 'end of an unnamed parameter' => ['void g(int * __cdecl);', ['(function (fn void ((attr __cdecl (ptr int)))) g)']];
        yield 'unnamed pointer to a function' => [
            'void f(int (__vectorcall *)(int));',
            ['(function (fn void ((ptr (fn int (int) __vectorcall)))) f)'],
        ];
        yield 'type name of a cast' => [
            'void *p = (void (__cdecl *)(int))0;',
            ['(variable (ptr void) p = (cast (ptr (fn void (int) __cdecl)) 0))'],
        ];
        yield 'with the GCC attributes of a parenthesized pointer to an object' => [
            'int (__attribute__((a)) __stdcall *p);',
            ['(variable (ptr (attr __attribute__((a)) __stdcall int)) p)'],
        ];
        yield 'object' => ['int __cdecl x;', ['(variable (attr __cdecl int) x)']];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('placesDataProvider')]
    public function testConventionAppliesToItsFunction(string $code, array $expected): void
    {
        self::assertSame($expected, \array_map(NodePrinter::print(...), self::parse($code)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, non-empty-string}>
     */
    public static function invalidPlacesDataProvider(): iterable
    {
        yield 'between the name and the parameters' => ['int f __cdecl (void);', 14, '('];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidPlacesDataProvider')]
    public function testConventionOutOfPlaceIsRejected(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }
}
