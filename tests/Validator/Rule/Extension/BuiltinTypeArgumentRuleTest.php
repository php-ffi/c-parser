<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule\Extension;

use FFI\Parser\Tests\Validator\Rule\RuleTestCase;
use FFI\Parser\Validator\Rule\Extension\BuiltinTypeArgumentRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(BuiltinTypeArgumentRule::class)]
final class BuiltinTypeArgumentRuleTest extends RuleTestCase
{
    private const MESSAGE = 'Argument of a function call should be an expression';

    protected static function createRule(): ValidatorRuleInterface
    {
        return new BuiltinTypeArgumentRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>}>
     */
    public static function typeArgumentsDataProvider(): iterable
    {
        yield 'offsetof' => ['int x = __builtin_offsetof(struct S, a);', 8];
        yield 'va_arg' => ['int x = __builtin_va_arg(ap, int);', 8];
        yield 'typedef names' => ['typedef int T; int x = __builtin_types_compatible_p(T, T);', 23];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('typeArgumentsDataProvider')]
    public function testTypeArgumentIsReported(string $code, int $offset): void
    {
        self::assertSame([[$offset, self::MESSAGE]], self::validate($code));
    }

    public function testCallWithExpressionsIsAccepted(): void
    {
        self::assertSame([], self::validate('int x = __builtin_expect(y, 1);'));
    }
}
