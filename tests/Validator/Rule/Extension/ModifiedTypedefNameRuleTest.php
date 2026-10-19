<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule\Extension;

use FFI\Parser\Tests\Validator\Rule\RuleTestCase;
use FFI\Parser\Validator\Rule\Extension\ModifiedTypedefNameRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ModifiedTypedefNameRule::class)]
final class ModifiedTypedefNameRuleTest extends RuleTestCase
{
    private const MESSAGE = 'Typedef name `T` should not be combined with other type specifiers';

    protected static function createRule(): ValidatorRuleInterface
    {
        return new ModifiedTypedefNameRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function modifiedTypedefNamesDataProvider(): iterable
    {
        yield 'modifier after a typedef name' => ['T unsigned x;'];
        yield 'qualifier among the modifiers' => ['T const long x;'];
        yield 'modifiers after a typedef name' => ['T long long x;'];
    }

    #[DataProvider('modifiedTypedefNamesDataProvider')]
    public function testModifiedTypedefNameIsReported(string $code): void
    {
        self::assertSame([[0, self::MESSAGE]], self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validTypesDataProvider(): iterable
    {
        yield 'typedef name alone' => ['T x;'];
        yield 'qualified typedef name' => ['const T x;'];
        yield 'unsigned long' => ['unsigned long x;'];
    }

    #[DataProvider('validTypesDataProvider')]
    public function testOtherTypeIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
