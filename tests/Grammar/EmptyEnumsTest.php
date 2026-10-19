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
final class EmptyEnumsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function enumerationsDataProvider(): iterable
    {
        yield 'enumeration' => ['enum E {};', '(tag enum E {  })'];
        yield 'anonymous enumeration of a variable' => ['enum {} e;', '(variable enum _ {  } e)'];
        yield 'enumeration with an underlying type' => ['enum E : int {};', '(tag enum E : int {  })'];
    }

    #[DataProvider('enumerationsDataProvider')]
    public function testEnumerationIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, \implode(' ', \array_map(NodePrinter::print(...), self::parse($code))));
    }

    public function testEmptyEnumerationIsRejectedAtItsBraceWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse('enum E {};');
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "EmptyEnums" is not enabled', 7, 1],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
