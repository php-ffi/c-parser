<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule;

use FFI\Parser\Validator\Rule\MemberDeclarationRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(MemberDeclarationRule::class)]
final class MemberDeclarationRuleTest extends RuleTestCase
{
    private const NOTHING = 'Member declaration should declare a member or an anonymous structure or union';

    protected static function createRule(): ValidatorRuleInterface
    {
        return new MemberDeclarationRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<array{int<0, max>, non-empty-string}>}>
     */
    public static function invalidMembersDataProvider(): iterable
    {
        yield 'type specifier alone' => ['struct S { int; };', [[11, self::NOTHING]]];
        yield 'enumeration' => ['struct S { enum { A }; };', [[11, self::NOTHING]]];
        yield 'function' => ['struct S { int f(void); };', [[11, 'Member should not have a function type']]];
        yield 'void' => ['struct S { const void v; };', [[11, 'Member should not have the incomplete type `void`']]];
        yield 'void with the attributes of a parenthesized declarator' => [
            'struct S { void (__attribute__((a)) v); };',
            [[11, 'Member should not have the incomplete type `void`']],
        ];
    }

    /**
     * @param list<array{int<0, max>, non-empty-string}> $expected
     */
    #[DataProvider('invalidMembersDataProvider')]
    public function testInvalidMemberIsReported(string $code, array $expected): void
    {
        self::assertSame($expected, self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validMembersDataProvider(): iterable
    {
        yield 'anonymous structure' => ['struct S { struct { int a; }; };'];
        yield 'qualified anonymous union' => ['struct S { const union { int a; float b; }; };'];
        yield 'unnamed bit-field' => ['struct S { int : 3; };'];
        yield 'structure with a tag' => ['struct S { struct T { int a; }; };'];
        yield 'reference to a structure' => ['struct S { struct T; };'];
        yield 'typedef name' => ['struct S { T; };'];
        yield 'pointer to a function' => ['struct S { int (*f)(void); };'];
        yield 'pointer to void' => ['struct S { void *p; };'];
    }

    #[DataProvider('validMembersDataProvider')]
    public function testValidMemberIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
