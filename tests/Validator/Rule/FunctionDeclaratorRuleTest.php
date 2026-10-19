<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule;

use FFI\Parser\Validator\Rule\FunctionDeclaratorRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FunctionDeclaratorRule::class)]
final class FunctionDeclaratorRuleTest extends RuleTestCase
{
    protected static function createRule(): ValidatorRuleInterface
    {
        return new FunctionDeclaratorRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, non-empty-string}>
     */
    public static function invalidFunctionsDataProvider(): iterable
    {
        yield 'function returning a function' => ['int f(void)(int);', 5, 'a function'];
        yield 'function returning an array' => ['int f(void)[3];', 5, 'an array'];
        yield 'pointer to a function returning an array' => ['int (*p)(void)[3];', 8, 'an array'];
        yield 'type name' => ['int s = sizeof(int (void)[3]);', 19, 'an array'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidFunctionsDataProvider')]
    public function testInvalidReturnTypeIsReported(string $code, int $offset, string $kind): void
    {
        self::assertSame([[$offset, 'Function should not return ' . $kind]], self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validFunctionsDataProvider(): iterable
    {
        yield 'function returning a pointer to a function' => ['int (*f(void))(int);'];
        yield 'function returning a pointer to an array' => ['int (*f(void))[3];'];
        yield 'function returning a pointer' => ['char *f(void);'];
    }

    #[DataProvider('validFunctionsDataProvider')]
    public function testValidReturnTypeIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
