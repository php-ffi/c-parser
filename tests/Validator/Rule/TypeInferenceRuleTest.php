<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule;

use FFI\Parser\Validator\Rule\TypeInferenceRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(TypeInferenceRule::class)]
final class TypeInferenceRuleTest extends RuleTestCase
{
    protected static function createRule(): ValidatorRuleInterface
    {
        return new TypeInferenceRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>}>
     */
    public static function missingTypeSpecifiersDataProvider(): iterable
    {
        yield 'storage class and an initializer' => ['static x = 1;', 0];
        yield 'qualifier and an initializer' => ['const x = 1;', 0];
        yield 'constexpr without auto' => ['constexpr x = 1;', 0];
        yield 'auto without an initializer' => ['extern auto *p;', 12];
        yield 'auto and a pointer' => ['auto *p = 0;', 5];
        yield 'qualified pointer' => ['const *p;', 0];
        yield 'function returning a pointer' => ['static *f(void);', 7];
        yield 'storage class alone' => ['static;', 0];
        yield 'parameter' => ['void f(register);', 7];
        yield 'member' => ['struct S { const *p; };', 11];
        yield 'type name' => ['int s = sizeof(const);', 15];
        yield 'type name of a compound literal' => ['int *p = (static alignas(4) [1]){0};', 17];
        yield 'typedef' => ['typedef *T;', 8];
        yield 'attributes of a parenthesized declarator' => ['static (__attribute__((x)) x);', 8];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('missingTypeSpecifiersDataProvider')]
    public function testMissingTypeSpecifierIsReported(string $code, int $offset): void
    {
        self::assertSame([[$offset, 'Type specifier is missing']], self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function typeSpecifiersDataProvider(): iterable
    {
        yield 'inferred type' => ['auto x = 1;'];
        yield 'inferred constant' => ['constexpr auto x = 1;'];
        yield 'inferred qualified type' => ['const auto x = 1;'];
        yield 'inferred type with the attributes of a parenthesized declarator' => ['auto (__attribute__((a)) x) = 1;'];
        yield 'inferred types of two declarators' => ['auto x = 1, y = 2;'];
        yield 'modifier alone' => ['unsigned x;'];
        yield 'typedef name' => ['typedef int T; static T x;'];
        yield 'pointer' => ['static int *p;'];
    }

    #[DataProvider('typeSpecifiersDataProvider')]
    public function testDeclarationWithTypeIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
