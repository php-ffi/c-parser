<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule\Extension;

use FFI\Parser\Tests\Validator\Rule\RuleTestCase;
use FFI\Parser\Validator\Rule\Extension\TaggedAnonymousMemberRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(TaggedAnonymousMemberRule::class)]
final class TaggedAnonymousMemberRuleTest extends RuleTestCase
{
    private const MESSAGE = 'Anonymous member should be a structure or union without a tag';

    protected static function createRule(): ValidatorRuleInterface
    {
        return new TaggedAnonymousMemberRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function taggedMembersDataProvider(): iterable
    {
        yield 'reference to a structure' => ['struct S { struct T; };'];
        yield 'structure with a tag' => ['struct S { union T { int a; }; };'];
        yield 'typedef name' => ['struct S { T; };'];
        yield 'qualified typedef name' => ['struct S { const T; };'];
    }

    #[DataProvider('taggedMembersDataProvider')]
    public function testTaggedAnonymousMemberIsReported(string $code): void
    {
        self::assertSame([[11, self::MESSAGE]], self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validMembersDataProvider(): iterable
    {
        yield 'anonymous structure' => ['struct S { struct { int a; }; };'];
        yield 'named member of a tagged structure' => ['struct S { struct T t; };'];
        yield 'unnamed bit-field' => ['struct S { T : 3; };'];
        yield 'type specifier alone' => ['struct S { int; };'];
    }

    #[DataProvider('validMembersDataProvider')]
    public function testOtherMemberIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
