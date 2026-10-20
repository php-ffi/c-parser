<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class ExtensionKeywordTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function placesDataProvider(): iterable
    {
        yield 'declaration' => ['__extension__ typedef long long T;', 'typedef long long T;'];
        yield 'repeated before a definition' => [
            '__extension__ __extension__ static int f(void) { return 0; }',
            'static int f(void) { return 0; }',
        ];
        yield 'static assertion' => ['__extension__ static_assert(1, "x");', 'static_assert(1, "x");'];
        yield 'member' => [
            'struct S { __extension__ union { int a; float b; }; int c; };',
            'struct S { union { int a; float b; }; int c; };',
        ];
        yield 'operand' => ['long long x = __extension__ 1LL + 2;', 'long long x = 1LL + 2;'];
        yield 'repeated before an operand' => ['int x = __extension__ __extension__ (1);', 'int x = (1);'];
        yield 'enumerator value' => ['enum { A = __extension__ 1 };', 'enum { A = 1 };'];
        yield 'extra semicolon' => ['int a; __extension__ ;', 'int a;'];
        yield 'extra semicolon among the members' => ['struct S { int a; __extension__ ; };', 'struct S { int a; };'];
        yield 'parameter' => ['void f(__extension__ int a, __extension__ long long b);', 'void f(int a, long long b);'];
        yield 'operand of a cast to a typedef name' => [
            'typedef int T; int x = (T)__extension__ 1;',
            'typedef int T; int x = (T)1;',
        ];
    }

    #[DataProvider('placesDataProvider')]
    public function testKeywordLeavesNoTrace(string $code, string $expected): void
    {
        self::assertSame(
            \array_map(NodePrinter::print(...), self::parse($expected)),
            \array_map(NodePrinter::print(...), self::parse($code)),
        );
    }

    public function testDeclarationStartsAfterTheKeyword(): void
    {
        self::assertSame(
            ['TypeDefDeclaration@14', 'SpecifierList@14', 'TypeDefSpecifier@14', 'Identifier@32', 'ModifiedType@14'],
            self::listOffsets(self::parse('__extension__ typedef long long T;')),
        );
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, non-empty-string}>
     */
    public static function invalidPlacesDataProvider(): iterable
    {
        yield 'among the specifiers' => ['int __extension__ x;', 4, '__extension__'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidPlacesDataProvider')]
    public function testKeywordOutOfPlaceIsRejected(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }
}
