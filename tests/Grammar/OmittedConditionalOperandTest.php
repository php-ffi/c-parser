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
final class OmittedConditionalOperandTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function expressionsDataProvider(): iterable
    {
        yield 'omitted operand' => ['int x = a ?: b;', '(variable int x = (? a _ b))'];
        yield 'condition of a binary operator' => ['int x = a || b ?: c;', '(variable int x = (? (|| a b) _ c))'];
        yield 'right-associative' => ['int x = a ?: b ?: c;', '(variable int x = (? a _ (? b _ c)))'];
        yield 'before a conditional operator' => ['int x = a ?: b ? c : d;', '(variable int x = (? a _ (? b c d)))'];
        yield 'after a conditional operator' => ['int x = a ? b : c ?: d;', '(variable int x = (? a b (? c _ d)))'];
        yield 'between the operands of a conditional operator' => [
            'int x = c ? a ?: b : d;',
            '(variable int x = (? c (? a _ b) d))',
        ];
    }

    #[DataProvider('expressionsDataProvider')]
    public function testExpressionIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, \implode(' ', \array_map(NodePrinter::print(...), self::parse($code))));
    }

    public function testOmittedOperandIsRejectedAtItsQuestionMarkWithoutTheFeature(): void
    {
        try {
            (new Parser(preset: Preset::c23()))->parse('int x = a ?: b;');
        } catch (SyntaxErrorException $e) {
            self::assertSame(
                ['Syntax error, the extension "OmittedConditionalOperand" is not enabled', 10, 1],
                [$e->getMessage(), $e->offset, $e->length],
            );

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
