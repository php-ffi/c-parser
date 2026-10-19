<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule;

use FFI\Parser\Validator\Rule\StorageClassSpecifierRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(StorageClassSpecifierRule::class)]
final class StorageClassSpecifierRuleTest extends RuleTestCase
{
    protected static function createRule(): ValidatorRuleInterface
    {
        return new StorageClassSpecifierRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function invalidCombinationsDataProvider(): iterable
    {
        yield 'static extern' => ['static extern int x;', '`static extern`'];
        yield 'repeated typedef' => ['typedef typedef int T;', '`typedef typedef`'];
        yield 'repeated static' => ['static static int x;', '`static static`'];
        yield 'typedef static' => ['typedef static int T;', '`typedef static`'];
        yield 'auto typedef' => ['auto typedef int T;', '`auto typedef`'];
        yield 'constexpr extern' => ['constexpr extern int x = 1;', '`constexpr extern`'];
        yield 'thread_local typedef' => ['thread_local typedef int T;', '`thread_local typedef`'];
        yield 'three specifiers' => ['static thread_local constexpr int x = 1;', '`static thread_local constexpr`'];
    }

    #[DataProvider('invalidCombinationsDataProvider')]
    public function testInvalidCombinationIsReported(string $code, string $specifiers): void
    {
        self::assertSame([[0, 'Invalid combination of storage-class specifiers ' . $specifiers]], self::validate($code));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validSpecifiersDataProvider(): iterable
    {
        yield 'static' => ['static int x;'];
        yield 'typedef' => ['typedef int T;'];
        yield 'extern function' => ['extern int f(void);'];
        yield 'static function definition' => ['static int f(void) { return 0; }'];
        yield 'extern thread_local' => ['extern thread_local int x;'];
        yield 'thread_local static' => ['thread_local static int x;'];
        yield 'constexpr' => ['constexpr int x = 1;'];
        yield 'static constexpr' => ['static constexpr int x = 1;'];
        yield 'inferred type' => ['auto x = 1;'];
        yield 'static inferred type' => ['static auto x = 1;'];
        yield 'register parameter' => ['void f(register int a);'];
        yield 'compound literal with constexpr' => ['int *p = (static constexpr int[]){1};'];
        yield 'no specifiers' => ['int x;'];
    }

    #[DataProvider('validSpecifiersDataProvider')]
    public function testValidSpecifiersAreAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
