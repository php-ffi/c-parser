<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule;

use FFI\Parser\Validator\Rule\FunctionDefinitionRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FunctionDefinitionRule::class)]
final class FunctionDefinitionRuleTest extends RuleTestCase
{
    private const NOT_FUNCTION = 'Declarator of a function definition should declare a function';

    protected static function createRule(): ValidatorRuleInterface
    {
        return new FunctionDefinitionRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<array{int<0, max>, non-empty-string}>}>
     */
    public static function invalidDefinitionsDataProvider(): iterable
    {
        yield 'object' => ['int x {}', [[0, self::NOT_FUNCTION]]];
        yield 'pointer to a function' => ['int (*f)(void) {}', [[0, self::NOT_FUNCTION]]];
        yield 'typedef name of a function type' => ['typedef int F(void); F f {}', [[21, self::NOT_FUNCTION]]];
        yield 'named void parameter with the attributes of a parenthesized declarator' => [
            'int f(void (__attribute__((a)) x)) {}',
            [[6, 'Parameter of type `void` should not have a name']],
        ];
        yield 'named void parameter' => ['int f(void x) {}', [[6, 'Parameter of type `void` should not have a name']]];
    }

    /**
     * @param list<array{int<0, max>, non-empty-string}> $expected
     */
    #[DataProvider('invalidDefinitionsDataProvider')]
    public function testInvalidDefinitionIsReported(string $code, array $expected): void
    {
        self::assertSame($expected, self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validDefinitionsDataProvider(): iterable
    {
        yield 'function without parameters' => ['int f(void) { return 0; }'];
        yield 'function returning a pointer to a function' => ['int (*f(int x))(void) { return 0; }'];
        yield 'function with an unnamed parameter' => ['int f(int) { return 0; }'];
        yield 'named void parameter of a declaration' => ['int f(void x);'];
    }

    #[DataProvider('validDefinitionsDataProvider')]
    public function testValidDefinitionIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
