<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks the external declarations of `resources/grammar/declarations.pp3`:
 * a header is compared as the list of its declarations printed with the
 * offsets of every node.
 */
#[CoversNothing]
final class DeclarationsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function declarationsDataProvider(): iterable
    {
        yield 'static assertion without a message' => ['static_assert(a);', ['(static_assert a@14)@0']];
        yield 'static assertion with a message' => [
            'static_assert(a, "message");',
            ['(static_assert a@14 "message"@17)@0'],
        ];
        yield 'static assertion with adjacent strings' => [
            'static_assert(a, "mes" "sage");',
            ['(static_assert a@14 (concat "mes"@17 "sage"@23)@17)@0'],
        ];
        yield 'underscored spelling of static_assert' => ['_Static_assert(a);', ['(static_assert a@15)@0']];
        yield 'declarations in order' => [
            "; static_assert(a);\n;; static_assert(b, \"b\");",
            ['(static_assert a@16)@2', '(static_assert b@37 "b"@40)@23'],
        ];
        yield 'tag declaration' => ['static struct S;', ['(tag static@0 struct S@7)@0']];
        yield 'type declaration' => ['const T;', ['(type [const T@6]@0)@0']];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('declarationsDataProvider')]
    public function testDeclarationsAreParsed(string $code, array $expected): void
    {
        self::assertSame($expected, \array_map(NodePrinter::printWithOffsets(...), self::parse($code)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, string}>
     */
    public static function invalidDeclarationsDataProvider(): iterable
    {
        yield 'missing semicolon' => ['static_assert(a)', 16, ''];
        yield 'missing condition' => ['static_assert();', 14, ')'];
        yield 'message is not a string' => ['static_assert(a, 1);', 17, '1'];
        yield 'two messages' => ['static_assert(a, "b", "c");', 20, ','];
        yield 'unclosed parenthesis' => ['static_assert(a;', 15, ';'];
        yield 'missing parentheses' => ['static_assert a;', 14, 'a'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidDeclarationsDataProvider')]
    public function testInvalidDeclarationIsRejected(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }
}
