<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule;

use FFI\Parser\Validator\Rule\AtomicTypeRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AtomicTypeRule::class)]
final class AtomicTypeRuleTest extends RuleTestCase
{
    protected static function createRule(): ValidatorRuleInterface
    {
        return new AtomicTypeRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function invalidTypesDataProvider(): iterable
    {
        yield 'array under a calling convention' => ['_Atomic(int (__cdecl)[3]) x;', 'an array'];
        yield 'array' => ['_Atomic(int[3]) x;', 'an array'];
        yield 'function' => ['_Atomic(int(void)) *p;', 'a function'];
        yield 'atomic type specifier' => ['_Atomic(_Atomic(int)) x;', 'an atomic'];
        yield 'atomic qualifier' => ['_Atomic(_Atomic int) x;', 'a qualified'];
        yield 'const' => ['_Atomic(const int) x;', 'a qualified'];
    }

    #[DataProvider('invalidTypesDataProvider')]
    public function testInvalidTypeNameIsReported(string $code, string $kind): void
    {
        self::assertSame(
            [[0, 'Atomic type specifier should not be applied to ' . $kind . ' type']],
            self::validate($code),
        );
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validTypesDataProvider(): iterable
    {
        yield 'int' => ['_Atomic(int) x;'];
        yield 'pointer' => ['_Atomic(int *) p;'];
        yield 'pointer to const' => ['_Atomic(const int *) p;'];
        yield 'structure' => ['_Atomic(struct S) s;'];
    }

    #[DataProvider('validTypesDataProvider')]
    public function testValidTypeNameIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
