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
final class SpecifierCallingConventionsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function declarationsDataProvider(): iterable
    {
        yield 'before the type specifier' => ['__cdecl int f(void);', '(function __cdecl (fn int (void)) f)'];
        yield 'after a storage-class specifier' => [
            'static __cdecl int f(void);',
            '(function static __cdecl (fn int (void)) f)',
        ];
        yield 'before a storage-class specifier' => [
            '__cdecl static unsigned long f(void);',
            '(function __cdecl static (fn (unsigned long _) (void)) f)',
        ];
        yield 'before a structure' => ['__cdecl struct S *f(void);', '(function __cdecl (fn (ptr struct S) (void)) f)'];
        yield 'before a typedef name' => [
            'typedef int T; __cdecl T f(void);',
            '(typedef typedef int T) (function __cdecl (fn T (void)) f)',
        ];
        yield 'before a typedef name and a pointer' => [
            'typedef int T; __cdecl T *f(void);',
            '(typedef typedef int T) (function __cdecl (fn (ptr T) (void)) f)',
        ];
        yield 'parameter' => ['void g(__cdecl int x);', '(function (fn void (__cdecl int x)) g)'];
        yield 'before a member' => ['struct S { __cdecl int a; };', '(tag struct S { __cdecl int a })'];
        yield 'without a declarator' => ['int __cdecl;', '(type __cdecl int)'];
        yield 'after a structure without a declarator' => [
            'struct S { int a; } __cdecl;',
            '(tag __cdecl struct S { int a })',
        ];
        yield 'member without a declarator' => ['struct S { int __cdecl; };', '(tag struct S { __cdecl int _ })'];
        yield 'before the name without a type specifier' => [
            'static __cdecl f(void);',
            '(function static (fn _ (void) __cdecl) f)',
        ];
    }

    #[DataProvider('declarationsDataProvider')]
    public function testDeclarationIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, \implode(' ', \array_map(NodePrinter::print(...), self::parse($code))));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>}>
     */
    public static function positionsDataProvider(): iterable
    {
        yield 'before the type specifier' => ['__cdecl int f(void);', 0];
        yield 'without a declarator' => ['int __cdecl;', 4];
    }

    /**
     * @param non-empty-string $code
     * @param int<0, max> $offset
     */
    #[DataProvider('positionsDataProvider')]
    public function testConventionIsRejectedAmongTheSpecifiersWithoutTheFeature(string $code, int $offset): void
    {
        try {
            (new Parser(preset: new Preset([Feature::CallingConventions])))->parse($code);
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "SpecifierCallingConventions" is not enabled', $offset, 7],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
