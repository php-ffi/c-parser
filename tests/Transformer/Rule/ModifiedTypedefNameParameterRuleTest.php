<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Transformer\Rule;

use FFI\Parser\Transformer\Rule\ModifiedTypedefNameParameterRule;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ModifiedTypedefNameParameterRule::class)]
final class ModifiedTypedefNameParameterRuleTest extends RuleTestCase
{
    protected static function createRule(): TransformerRuleInterface
    {
        return new ModifiedTypedefNameParameterRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function parametersDataProvider(): iterable
    {
        yield 'modifier' => ['void r(unsigned T);', '(function (fn void ((unsigned T))) r)'];
        yield 'qualified modifiers' => ['void r(const long long T);', '(function (fn void ([const (long long T)])) r)'];
    }

    #[DataProvider('parametersDataProvider')]
    public function testParameterNamedByTypedefNameIsTurnedIntoType(string $code, string $expected): void
    {
        self::assertSame($expected, self::transform($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function keptParametersDataProvider(): iterable
    {
        yield 'name other than a typedef name' => ['void r(unsigned x);'];
        yield 'modifiers with a base' => ['void r(unsigned int T);'];
        yield 'type other than modifiers' => ['void r(int T);'];
    }

    #[DataProvider('keptParametersDataProvider')]
    public function testOtherParameterIsKept(string $code): void
    {
        self::assertDeclarationIsKept($code);
    }
}
