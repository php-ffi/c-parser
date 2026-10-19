<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class ModifiedTypedefNamesTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function typesDataProvider(): iterable
    {
        yield 'before a declarator' => ['unsigned __int128 x;', '(variable (unsigned __int128) x)'];
        yield 'before a pointer' => ['unsigned T *p;', '(variable (ptr (unsigned T)) p)'];
        yield 'before a qualifier' => ['unsigned T const *p;', '(variable (ptr [const (unsigned T)]) p)'];
        yield 'before a calling convention' => [
            'unsigned __int64 __cdecl f(void);',
            '(function (fn (unsigned __int64) (void) __cdecl) f)',
        ];
        yield 'before a parenthesized declarator' => [
            'typedef unsigned __int64 (__stdcall *PFN)(void);',
            '(typedef typedef (ptr (fn (unsigned __int64) (void) __stdcall)) PFN)',
        ];
        yield 'before the name of a built-in function' => [
            'unsigned __int64 __builtin_x(int);',
            '(function (fn (unsigned __int64) (int)) __builtin_x)',
        ];
        yield 'member' => ['struct S { unsigned __int128 c : 70; };', '(tag struct S { (unsigned __int128) c:70 })'];
        yield 'type name of a cast' => ['int x = (unsigned __int64)1;', '(variable int x = (cast (unsigned __int64) 1))'];
        yield 'type name of sizeof' => [
            'int x = sizeof(unsigned __int64 *);',
            '(variable int x = (sizeof (ptr (unsigned __int64))))',
        ];
        yield 'unnamed parameter of a typedef name' => [
            'typedef long long __int64; void f(unsigned __int64);',
            '(function (fn void ((unsigned __int64))) f)',
        ];
        yield 'name alone' => ['unsigned T;', '(variable (unsigned _) T)'];
        yield 'array' => ['unsigned T[3];', '(variable (array (unsigned _) 3) T)'];
        yield 'parameter' => ['void f(unsigned T);', '(function (fn void ((unsigned _) T)) f)'];
        yield 'function' => ['unsigned T (x);', '(function (fn (unsigned _) (x)) T)'];
    }

    #[DataProvider('typesDataProvider')]
    public function testTypeIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(\array_last(self::parse($code))));
    }
}
