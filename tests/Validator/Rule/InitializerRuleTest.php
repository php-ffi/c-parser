<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule;

use FFI\Parser\Validator\Rule\InitializerRule;
use FFI\Parser\Validator\ValidatorRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(InitializerRule::class)]
final class InitializerRuleTest extends RuleTestCase
{
    protected static function createRule(): ValidatorRuleInterface
    {
        return new InitializerRule();
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, non-empty-string}>
     */
    public static function invalidInitializersDataProvider(): iterable
    {
        yield 'function' => ['int f(void) = 0;', 14, 'a function'];
        yield 'typedef name' => ['typedef int T = 1;', 16, 'a typedef name'];
        yield 'second declarator' => ['int x = 1, f(void) = { 0 };', 21, 'a function'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidInitializersDataProvider')]
    public function testInitializerIsReported(string $code, int $offset, string $kind): void
    {
        self::assertSame(
            [[$offset, 'Initializer is not allowed in the declaration of ' . $kind]],
            self::validate($code),
        );
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string}>
     */
    public static function validInitializersDataProvider(): iterable
    {
        yield 'object' => ['int x = 1;'];
        yield 'array of unknown size' => ['int a[] = {1};'];
        yield 'array of a known size with an empty initializer' => ['int a[2] = {};'];
        yield 'array of unknown size with an empty initializer' => ['int a[] = {};'];
        yield 'pointer to a function' => ['int (*p)(void) = 0;'];
        yield 'function without an initializer' => ['int f(void);'];
        yield 'typedef without an initializer' => ['typedef int T;'];
    }

    #[DataProvider('validInitializersDataProvider')]
    public function testValidDeclarationIsAccepted(string $code): void
    {
        self::assertSame([], self::validate($code));
    }
}
