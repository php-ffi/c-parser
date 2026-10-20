<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Parser;
use FFI\Parser\Preset;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class OptionalLastMemberSemicolonTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function membersDataProvider(): iterable
    {
        yield 'only member' => ['struct S { int a };', '(tag struct S { int a })'];
        yield 'after another member' => ['struct S { int a; int b };', '(tag struct S { int a; int b })'];
        yield 'several declarators' => ['struct S { int a, b };', '(tag struct S { int a; int b })'];
        yield 'bit-field' => ['struct S { int a : 3 };', '(tag struct S { int a:3 })'];
        yield 'anonymous union after __extension__' => [
            'struct S { __extension__ union { int x; } };',
            '(tag struct S { union _ { int x } _ })',
        ];
        yield 'static assertion' => [
            'struct S { int a; static_assert(1) };',
            '(tag struct S { int a; (static_assert 1) })',
        ];
    }

    #[DataProvider('membersDataProvider')]
    public function testMemberIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(self::parseSingle($code, RootDeclarationInterface::class)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, non-empty-string}>
     */
    public static function invalidMembersDataProvider(): iterable
    {
        yield 'declarator without a comma' => ['struct S { int a b; };', 17, 'b'];
        yield 'static assertion before a member' => ['struct S { static_assert(1) int a; };', 28, 'int'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidMembersDataProvider')]
    public function testInvalidMemberIsRejected(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }

    public function testMemberIsRejectedWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse('struct S { int a; int b };');
        } catch (SyntaxErrorException $e) {
            self::assertSame([18, 'int b'], [$e->offset, \substr('struct S { int a; int b };', $e->offset, $e->length ?? 0)]);

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
