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
final class LeadingCallingConventionsTest extends TestCase
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
        yield 'parameter' => ['void g(__cdecl int x);', '(function (fn void (__cdecl int x)) g)'];
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

    public function testConventionIsRejectedBeforeTheTypeSpecifierWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: new Preset([Feature::CallingConventions])))->parse('__cdecl int f(void);');
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "LeadingCallingConventions" is not enabled', 0, 7],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
