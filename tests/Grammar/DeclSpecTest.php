<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class DeclSpecTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function specifiersDataProvider(): iterable
    {
        yield 'before the specifiers' => [
            '__declspec(dllimport) int f(void);',
            '(function __declspec(dllimport) (fn int (void)) f)',
        ];
        yield 'among the specifiers' => [
            'int __declspec(selectany) const x = 1;',
            '(variable __declspec(selectany) [const int] x = 1)',
        ];
        yield 'several attributes' => [
            '__declspec(dllimport align(8)) int x;',
            '(variable __declspec(dllimport) __declspec(align(8)) int x)',
        ];
        yield 'empty list' => ['__declspec() int x;', '(variable int x)'];
        yield 'keyword as a name' => [
            '__declspec(noreturn) void f(void);',
            '(function __declspec(noreturn) (fn void (void)) f)',
        ];
        yield 'expression as an argument' => [
            '__declspec(align(sizeof(int) * 2)) int x;',
            '(variable __declspec(align((* (sizeof int) 2))) int x)',
        ];
        yield 'tokens as arguments' => [
            '__declspec(property(get=GetX, put=PutX)) int x;',
            '(variable __declspec(property((tokens get=GetX), (tokens put=PutX))) int x)',
        ];
        yield 'after struct' => [
            'struct __declspec(align(16)) S { int a; };',
            '(tag struct __declspec(align(16)) S { int a })',
        ];
        yield 'before the specifier of an anonymous structure' => [
            'typedef __declspec(align(32)) struct { int a; } T;',
            '(typedef typedef __declspec(align(32)) struct _ { int a } T)',
        ];
        yield 'after the declarator' => [
            'int f(void) __declspec(dllimport);',
            '(function (fn int (void)) f __declspec(dllimport))',
        ];
        yield 'after a pointer' => ['int * __declspec(dllimport) p;', '(variable (ptr int __declspec(dllimport)) p)'];
        yield 'names of the attributes outside of the specifier' => ['int dllimport;', '(variable int dllimport)'];
    }

    #[DataProvider('specifiersDataProvider')]
    public function testSpecifierIsParsed(string $code, string $expected): void
    {
        self::assertSame([$expected], \array_map(NodePrinter::print(...), self::parse($code)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, non-empty-string}>
     */
    public static function invalidSpecifiersDataProvider(): iterable
    {
        yield 'without parentheses' => ['__declspec dllimport int x;', 11, 'dllimport'];
        yield 'comma between the attributes' => ['__declspec(a, b) int x;', 12, ','];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidSpecifiersDataProvider')]
    public function testInvalidSpecifierIsRejected(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }
}
