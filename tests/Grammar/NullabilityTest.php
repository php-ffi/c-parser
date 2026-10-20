<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Parser;
use FFI\Parser\Preset;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class NullabilityTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function declarationsDataProvider(): iterable
    {
        yield 'after the pointer' => ['int * _Nonnull p;', '(variable (ptr int _Nonnull) p)'];
        yield 'before the qualifiers' => ['int * _Nonnull const p;', '(variable [const (ptr int _Nonnull)] p)'];
        yield 'every pointer' => [
            'int * _Nullable_result * _Null_unspecified p;',
            '(variable (ptr (ptr int _Nullable_result) _Null_unspecified) p)',
        ];
        yield 'among the specifiers' => ['_Nullable int *p;', '(variable _Nullable (ptr int) p)'];
        yield 'after a typedef name' => [
            'typedef int *T; T _Nonnull s;',
            '(typedef typedef (ptr int) T) (variable _Nonnull T s)',
        ];
        yield 'member' => ['struct S { int * _Nonnull a; };', '(tag struct S { (ptr int _Nonnull) a })'];
        yield 'end of an unnamed parameter' => [
            'void f(int * _Nullable);',
            '(function (fn void ((ptr int _Nullable))) f)',
        ];
        yield 'type name' => [
            'int x = (int)(int * _Nonnull)p;',
            '(variable int x = (cast int (cast (ptr int _Nonnull) p)))',
        ];
        yield 'after a block pointer' => [
            'void (^ _Nonnull b)(void);',
            '(variable (block (fn void (void)) _Nonnull) b)',
        ];
        yield 'in the brackets of an array' => [
            'void f(int a[_Nonnull 3]);',
            '(function (fn void ((array int 3 _Nonnull) a)) f)',
        ];
    }

    #[DataProvider('declarationsDataProvider')]
    public function testDeclarationIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, \implode(' ', \array_map(NodePrinter::print(...), self::parse($code))));
    }

    public function testNullabilityIsRejectedAtItsKeywordWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse('int * _Nonnull p;');
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "Nullability" is not enabled', 6, 8],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
