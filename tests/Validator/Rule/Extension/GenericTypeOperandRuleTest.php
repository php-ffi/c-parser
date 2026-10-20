<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule\Extension;

use FFI\Parser\Tests\Validator\Rule\RuleTestCase;
use FFI\Parser\Validator\Rule\Extension\GenericTypeOperandRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(GenericTypeOperandRule::class)]
final class GenericTypeOperandRuleTest extends RuleTestCase
{
    private const MESSAGE = 'Controlling operand of a generic selection should be an expression';

    protected static function createRule(): ValidatorRuleInterface
    {
        return new GenericTypeOperandRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>}>
     */
    public static function typeOperandsDataProvider(): iterable
    {
        yield 'type name' => ['int x = _Generic(int, int: 1);', 17];
        yield 'typedef name' => ['typedef int T; int x = _Generic(T, int: 1);', 32];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('typeOperandsDataProvider')]
    public function testTypeOperandIsReported(string $code, int $offset): void
    {
        self::assertSame([[$offset, self::MESSAGE]], self::validate($code));
    }

    public function testExpressionOperandIsAccepted(): void
    {
        self::assertSame([], self::validate('int a; int x = _Generic(a, int: 1);'));
    }
}
