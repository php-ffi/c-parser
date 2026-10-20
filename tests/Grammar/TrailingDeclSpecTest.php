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
final class TrailingDeclSpecTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function declarationsDataProvider(): iterable
    {
        yield 'after the declarator' => [
            'int f(void) __declspec(dllimport);',
            '(function (fn int (void)) f __declspec(dllimport))',
        ];
        yield 'after the pointer' => ['int * __declspec(dllimport) p;', '(variable (ptr int __declspec(dllimport)) p)'];
        yield 'after a member' => [
            'struct S { int a __declspec(align(8)); };',
            '(tag struct S { int a __declspec(align(8)) })',
        ];
        yield 'in the brackets of an array' => [
            'void f(int a[__declspec(x) 3]);',
            '(function (fn void ((array int 3 __declspec(x)) a)) f)',
        ];
        yield 'start of a parenthesized declarator' => [
            'int (__declspec(dllimport) *f)(void);',
            '(variable (ptr (fn int (void) __declspec(dllimport))) f)',
        ];
        yield 'start of a parenthesized abstract declarator' => [
            'int s = sizeof(int (__declspec(x) *)(void));',
            '(variable int s = (sizeof (ptr (fn int (void) __declspec(x)))))',
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
        yield 'after the declarator' => ['int f(void) __declspec(dllimport);', 12];
        yield 'after the pointer' => ['int * __declspec(dllimport) p;', 6];
    }

    /**
     * @param non-empty-string $code
     * @param int<0, max> $offset
     */
    #[DataProvider('positionsDataProvider')]
    public function testAttributeIsRejectedOutOfTheSpecifiersWithoutTheFeature(string $code, int $offset): void
    {
        try {
            (new Parser(preset: new Preset([Feature::DeclSpec])))->parse($code);
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "TrailingDeclSpec" is not enabled', $offset, 20],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
