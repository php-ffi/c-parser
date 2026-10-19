<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule\Extension;

use FFI\Parser\Tests\Validator\Rule\RuleTestCase;
use FFI\Parser\Validator\Rule\Extension\GnuFlexibleArrayMemberRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(GnuFlexibleArrayMemberRule::class)]
final class GnuFlexibleArrayMemberRuleTest extends RuleTestCase
{
    protected static function createRule(): ValidatorRuleInterface
    {
        return new GnuFlexibleArrayMemberRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<array{int<0, max>, non-empty-string}>}>
     */
    public static function invalidRecordsDataProvider(): iterable
    {
        yield 'flexible array member in a union' => [
            'union U { int n; int a[]; };',
            [[17, 'Flexible array member should not be a member of a union']],
        ];
        yield 'flexible array member with the attributes of a parenthesized declarator' => [
            'union U { int n; int (__attribute__((x)) a)[]; };',
            [[17, 'Flexible array member should not be a member of a union']],
        ];
        yield 'flexible array member alone in a union' => [
            'union U { int a[]; };',
            [[10, 'Flexible array member should not be a member of a union']],
        ];
        yield 'flexible array member alone' => [
            'struct S { int a[]; };',
            [[11, 'Flexible array member should not be the only member']],
        ];
        yield 'flexible array member after an unnamed bit-field' => [
            'struct S { int : 3; int a[]; };',
            [[20, 'Flexible array member should not be the only member']],
        ];
    }

    /**
     * @param list<array{int<0, max>, non-empty-string}> $expected
     */
    #[DataProvider('invalidRecordsDataProvider')]
    public function testInvalidRecordIsReported(string $code, array $expected): void
    {
        self::assertSame($expected, self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validRecordsDataProvider(): iterable
    {
        yield 'flexible array member' => ['struct S { int n; int a[]; };'];
        yield 'flexible array member after an anonymous structure' => ['struct S { struct { int n; }; int a[]; };'];
        yield 'array of a known size in a union' => ['union U { int n; int a[3]; };'];
        yield 'structure without a body' => ['struct S;'];
    }

    #[DataProvider('validRecordsDataProvider')]
    public function testValidRecordIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
