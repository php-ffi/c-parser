<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule;

use FFI\Parser\Validator\Rule\ArrayDeclaratorRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ArrayDeclaratorRule::class)]
final class ArrayDeclaratorRuleTest extends RuleTestCase
{
    protected static function createRule(): ValidatorRuleInterface
    {
        return new ArrayDeclaratorRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<array{int<0, max>, non-empty-string}>}>
     */
    public static function invalidArraysDataProvider(): iterable
    {
        yield 'array of functions' => ['int a[3](int);', [[5, 'Element type of an array should not be a function']]];
        yield 'array of void' => ['void a[3];', [[6, 'Element type of an array should not be `void`']]];
        yield 'array of qualified void' => [
            'const void a[3];',
            [[12, 'Element type of an array should not be `void`']],
        ];
    }

    /**
     * @param list<array{int<0, max>, non-empty-string}> $expected
     */
    #[DataProvider('invalidArraysDataProvider')]
    public function testInvalidArrayIsReported(string $code, array $expected): void
    {
        self::assertSame($expected, self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validArraysDataProvider(): iterable
    {
        yield 'qualified parameters' => ['void f(int a[static 3], int b[const], int c[restrict static 3]);'];
        yield 'qualified parameter of a prototype in a type name' => ['int s = sizeof(void (*)(int a[const 3]));'];
        yield 'unspecified size of a parameter' => ['void f(int a[*]);'];
        yield 'unspecified size of an inner array of a parameter' => ['void f(int a[][*]);'];
        yield 'unspecified size of an array a parameter points to' => ['void f(int (*a)[*]);'];
        yield 'qualified unspecified size of a named parameter' => ['void f(int a[const *]);'];
        yield 'unspecified size of an abstract parameter' => ['void f(int [*]);'];
        yield 'unspecified size in a prototype of a parameter' => ['void f(void (*g)(int a[*]));'];
        yield 'unspecified size in a function returned by a definition' => ['void (*f(void))(int a[*]) {}'];
        yield 'array of arrays' => ['int a[3][4];'];
        yield 'array of pointers to functions' => ['int (*a[3])(int);'];
        yield 'array of pointers to void' => ['void *a[3];'];
        yield 'flexible array member' => ['struct S { int n; char data[]; };'];
        yield 'qualifier of an object' => ['int a[const 3];'];
        yield 'unspecified size of an object' => ['int a[*];'];
    }

    #[DataProvider('validArraysDataProvider')]
    public function testValidArrayIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
