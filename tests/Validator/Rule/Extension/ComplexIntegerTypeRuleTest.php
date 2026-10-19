<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule\Extension;

use FFI\Parser\Tests\Validator\Rule\RuleTestCase;
use FFI\Parser\Validator\Rule\Extension\ComplexIntegerTypeRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ComplexIntegerTypeRule::class)]
final class ComplexIntegerTypeRuleTest extends RuleTestCase
{
    private const MESSAGE = 'Type specifier `_Complex` should be applied to a floating type';

    protected static function createRule(): ValidatorRuleInterface
    {
        return new ComplexIntegerTypeRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function complexIntegersDataProvider(): iterable
    {
        yield 'complex int' => ['_Complex int x;'];
        yield 'complex unsigned long long' => ['unsigned long long _Complex x;'];
        yield 'complex character' => ['_Complex signed char x;'];
        yield 'complex alone' => ['_Complex x;'];
    }

    #[DataProvider('complexIntegersDataProvider')]
    public function testComplexIntegerIsReported(string $code): void
    {
        self::assertSame([[0, self::MESSAGE]], self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validTypesDataProvider(): iterable
    {
        yield 'complex double' => ['_Complex double x;'];
        yield 'complex long double' => ['long double _Complex x;'];
        yield 'complex interchange type' => ['_Float32 _Complex x;'];
        yield 'unsigned int' => ['unsigned int x;'];
        yield 'imaginary integer' => ['_Imaginary int x;'];
    }

    #[DataProvider('validTypesDataProvider')]
    public function testOtherTypeIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
