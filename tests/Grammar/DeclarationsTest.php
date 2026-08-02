<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Exception\SyntaxException;
use FFI\Parser\Node\Declaration\StaticAssertDeclaration;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Literal\ConcatenatedStringLiteral;
use FFI\Parser\Node\Literal\StringLiteral;
use FFI\Parser\Parser;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks the external declarations of `resources/grammar/declarations.pp3`.
 */
#[CoversNothing]
final class DeclarationsTest extends TestCase
{
    public function testStaticAssertWithoutMessageIsParsed(): void
    {
        $declaration = self::parseStaticAssert('static_assert(a);');

        self::assertInstanceOf(IdentifierExpression::class, $declaration->condition);
        self::assertNull($declaration->message);
        self::assertSame(0, $declaration->offset);
    }

    public function testStaticAssertWithMessageIsParsed(): void
    {
        $declaration = self::parseStaticAssert('static_assert(a, "message");');

        self::assertInstanceOf(StringLiteral::class, $declaration->message);
        self::assertSame('message', $declaration->message->value);
    }

    public function testStaticAssertWithAdjacentStringsIsParsed(): void
    {
        $declaration = self::parseStaticAssert('static_assert(a, "mes" "sage");');

        self::assertInstanceOf(ConcatenatedStringLiteral::class, $declaration->message);
        self::assertSame(17, $declaration->message->offset);
        self::assertSame(
            [['mes', 17], ['sage', 23]],
            \array_map(
                static fn(StringLiteral $part): array => [$part->value, $part->offset],
                $declaration->message->parts,
            ),
        );
    }

    public function testUnderscoredSpellingOfStaticAssertIsParsed(): void
    {
        $declaration = self::parseStaticAssert('_Static_assert(a);');

        self::assertInstanceOf(IdentifierExpression::class, $declaration->condition);
        self::assertSame(15, $declaration->condition->offset);
        self::assertNull($declaration->message);
    }

    public function testDeclarationsAreListedInOrder(): void
    {
        $nodes = (new Parser())->parse("; static_assert(a);\n;; static_assert(b, \"b\");");

        self::assertCount(2, $nodes);
        self::assertContainsOnlyInstancesOf(StaticAssertDeclaration::class, $nodes);
        self::assertSame([2, 23], [$nodes[0]->offset, $nodes[1]->offset]);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>}>
     */
    public static function invalidDeclarationsDataProvider(): iterable
    {
        yield 'missing semicolon' => ['static_assert(a)', 16];
        yield 'missing condition' => ['static_assert();', 14];
        yield 'message is not a string' => ['static_assert(a, 1);', 17];
        yield 'two messages' => ['static_assert(a, "b", "c");', 20];
        yield 'unclosed parenthesis' => ['static_assert(a;', 15];
        yield 'missing parentheses' => ['static_assert a;', 14];
    }

    #[DataProvider('invalidDeclarationsDataProvider')]
    public function testInvalidDeclarationIsRejected(string $code, int $offset): void
    {
        try {
            (new Parser())->parse($code);
        } catch (SyntaxException $e) {
            self::assertSame($offset, $e->offset);

            return;
        }

        self::fail('SyntaxException has not been thrown');
    }

    /**
     * @param non-empty-string $code
     */
    private static function parseStaticAssert(string $code): StaticAssertDeclaration
    {
        $nodes = (new Parser())->parse($code);

        self::assertCount(1, $nodes);
        self::assertInstanceOf(StaticAssertDeclaration::class, $nodes[0]);

        return $nodes[0];
    }
}
