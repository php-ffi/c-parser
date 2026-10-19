<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule;

use FFI\Parser\Validator\Rule\StructTypeRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(StructTypeRule::class)]
final class StructTypeRuleTest extends RuleTestCase
{
    protected static function createRule(): ValidatorRuleInterface
    {
        return new StructTypeRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<array{int<0, max>, non-empty-string}>}>
     */
    public static function invalidStructuresDataProvider(): iterable
    {
        yield 'flexible array member before another member' => [
            'struct S { int n; int a[]; int b; };',
            [[18, 'Flexible array member is allowed only as the last member']],
        ];
        yield 'flexible array member with the attributes of a parenthesized declarator' => [
            'struct S { int n; int (__attribute__((x)) a)[]; int b; };',
            [[18, 'Flexible array member is allowed only as the last member']],
        ];
    }

    /**
     * @param list<array{int<0, max>, non-empty-string}> $expected
     */
    #[DataProvider('invalidStructuresDataProvider')]
    public function testInvalidStructureIsReported(string $code, array $expected): void
    {
        self::assertSame($expected, self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validStructuresDataProvider(): iterable
    {
        yield 'attributes of a definition' => ['struct [[a]] S { int a; } x;'];
        yield 'attributes of a tag declaration' => ['struct [[a]] S;'];
        yield 'flexible array member' => ['struct S { int n; int a[]; };'];
        yield 'flexible array member before a static assertion' => ['struct S { int n; int a[]; static_assert(1); };'];
        yield 'flexible array member after an anonymous structure' => ['struct S { struct { int n; }; int a[]; };'];
        yield 'flexible array member in a union' => ['union U { int n; int a[]; int b; };'];
        yield 'attributes of a structure in a declarator declaration' => ['struct [[a]] S x;'];
        yield 'flexible array member alone' => ['struct S { int a[]; };'];
    }

    #[DataProvider('validStructuresDataProvider')]
    public function testValidStructureIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
