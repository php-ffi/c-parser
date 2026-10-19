<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule\Extension;

use FFI\Parser\Tests\Validator\Rule\RuleTestCase;
use FFI\Parser\Validator\Rule\Extension\AlignOfExpressionRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AlignOfExpressionRule::class)]
final class AlignOfExpressionRuleTest extends RuleTestCase
{
    private const MESSAGE = 'Operand of alignof should be a type name';

    protected static function createRule(): ValidatorRuleInterface
    {
        return new AlignOfExpressionRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>}>
     */
    public static function expressionsDataProvider(): iterable
    {
        yield 'expression without parentheses' => ['int a; int x = alignof a;', 15];
        yield 'parenthesized object' => ['int a; int x = alignof(a);', 15];
        yield 'compound literal' => ['int x = alignof (int){1};', 8];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('expressionsDataProvider')]
    public function testExpressionOperandIsReported(string $code, int $offset): void
    {
        self::assertSame([[$offset, self::MESSAGE]], self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function typeNamesDataProvider(): iterable
    {
        yield 'type name' => ['int x = alignof(long);'];
        yield 'typedef name' => ['typedef int T; int x = alignof(T);'];
        yield 'undeclared name' => ['int x = alignof(T[3]);'];
    }

    #[DataProvider('typeNamesDataProvider')]
    public function testTypeNameOperandIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
