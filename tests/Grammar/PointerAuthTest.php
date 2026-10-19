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
final class PointerAuthTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function declarationsDataProvider(): iterable
    {
        yield 'after the pointer' => [
            'void * __ptrauth(1, 1, 0x1234) p;',
            '(variable (ptr void __ptrauth(1, 1, 0x1234)) p)',
        ];
        yield 'among the specifiers' => ['__ptrauth(2, 0, 0) void *p;', '(variable __ptrauth(2, 0, 0) (ptr void) p)'];
        yield 'member pointer to a function' => [
            'struct S { void (* __ptrauth(0, 1, 7) f)(void); };',
            '(tag struct S { (ptr (fn void (void)) __ptrauth(0, 1, 7)) f })',
        ];
    }

    #[DataProvider('declarationsDataProvider')]
    public function testDeclarationIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, \implode(' ', \array_map(NodePrinter::print(...), self::parse($code))));
    }

    public function testSigningIsRejectedAtItsKeywordWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse('void * __ptrauth(1, 0, 0) p;');
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "PointerAuth" is not enabled', 7, 9],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
