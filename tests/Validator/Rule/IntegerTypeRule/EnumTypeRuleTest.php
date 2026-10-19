<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule\IntegerTypeRule;

use FFI\Parser\Tests\Validator\Rule\RuleTestCase;
use FFI\Parser\Validator\Rule\IntegerTypeRule\EnumTypeRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(EnumTypeRule::class)]
final class EnumTypeRuleTest extends RuleTestCase
{
    private const NOT_INTEGER = 'Underlying type of an enumeration should be an integer type';

    private const ENUM_OR_BIT_INT = 'Underlying type of an enumeration should not be an enumeration or a bit-precise '
        . 'integer type';

    private const WITHOUT_ENUMERATORS = 'Enumeration with an underlying type and without enumerators is allowed only '
        . 'in the declaration of its tag';

    protected static function createRule(): ValidatorRuleInterface
    {
        return new EnumTypeRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<array{int<0, max>, non-empty-string}>}>
     */
    public static function invalidEnumerationsDataProvider(): iterable
    {
        yield 'floating underlying type' => ['enum E : float { A };', [[0, self::NOT_INTEGER]]];
        yield 'long double underlying type' => ['enum E : long double { A };', [[0, self::NOT_INTEGER]]];
        yield 'structure as the underlying type' => ['enum E : struct S;', [[0, self::NOT_INTEGER]]];
        yield 'enumeration as the underlying type' => ['enum E : enum F { A };', [[0, self::ENUM_OR_BIT_INT]]];
        yield 'bit-precise underlying type' => ['enum E : unsigned _BitInt(8) { A };', [[0, self::ENUM_OR_BIT_INT]]];
        yield 'underlying type in a declarator declaration' => ['enum E : int x;', [[0, self::WITHOUT_ENUMERATORS]]];
        yield 'underlying type in a qualified declaration' => ['const enum E : int;', [[6, self::WITHOUT_ENUMERATORS]]];
        yield 'underlying type in a member' => [
            'struct S { enum E : int e; };',
            [[11, self::WITHOUT_ENUMERATORS]],
        ];
    }

    /**
     * @param list<array{int<0, max>, non-empty-string}> $expected
     */
    #[DataProvider('invalidEnumerationsDataProvider')]
    public function testInvalidEnumerationIsReported(string $code, array $expected): void
    {
        self::assertSame($expected, self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validEnumerationsDataProvider(): iterable
    {
        yield 'int' => ['enum E : int { A };'];
        yield 'qualified unsigned long' => ['enum E : const unsigned long { A };'];
        yield 'char' => ['enum E : char { A };'];
        yield 'bool' => ['enum E : bool { A };'];
        yield 'typedef name' => ['typedef int T; enum E : T { A };'];
        yield 'declaration of the tag' => ['enum E : short;'];
        yield 'without an underlying type' => ['enum E { A }; enum E x;'];
    }

    #[DataProvider('validEnumerationsDataProvider')]
    public function testValidEnumerationIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
