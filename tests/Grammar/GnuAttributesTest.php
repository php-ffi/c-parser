<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Node\Declaration\RootDeclarationInterface;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class GnuAttributesTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function placesDataProvider(): iterable
    {
        yield 'before the specifiers' => [
            '__attribute__((packed)) int a;',
            ['(variable __attribute__((packed)) int a)'],
        ];
        yield 'among the specifiers' => [
            'int __attribute__((x)) const a;',
            ['(variable __attribute__((x)) [const int] a)'],
        ];
        yield 'short spelling' => [
            '__attribute((packed)) int a;',
            ['(variable __attribute__((packed)) int a)'],
        ];
        yield 'after a function declarator' => [
            'extern int f(void) __attribute__((__nothrow__, __leaf__)) __attribute__((__nonnull__(1)));',
            [
                '(function extern (fn int (void)) f __attribute__((__nothrow__)) __attribute__((__leaf__))'
                . ' __attribute__((__nonnull__(1))))',
            ],
        ];
        yield 'after a nested declarator' => [
            'void (*f)(int) __attribute__((x));',
            ['(variable (ptr (fn void (int))) f __attribute__((x)))'],
        ];
        yield 'after the name before the initializer' => [
            'int x __attribute__((aligned(8))) = 1;',
            ['(variable int x __attribute__((aligned(8))) = 1)'],
        ];
        yield 'before a declarator of the list' => [
            'int a, __attribute__((x)) b;',
            ['(variable int a)', '(variable int b __attribute__((x)))'],
        ];
        yield 'after a function definition' => [
            'int f(void) __attribute__((x)) { return 0; }',
            ['(definition (fn int (void)) f __attribute__((x)) { return 0; })'],
        ];
        yield 'after a typedef name' => [
            'typedef int int8_t __attribute__((__mode__(__QI__)));',
            ['(typedef typedef int int8_t __attribute__((__mode__(__QI__))))'],
        ];
        yield 'after a pointer qualifier' => [
            'int *const __attribute__((x)) p;',
            ['(variable [const (ptr int __attribute__((x)))] p)'],
        ];
        yield 'after a parameter' => [
            'void f(int x __attribute__((unused)), int (*g)(void) __attribute__((y)));',
            ['(function (fn void (int x __attribute__((unused)), (ptr (fn int (void))) g __attribute__((y)))) f)'],
        ];
        yield 'after struct' => [
            'struct __attribute__((packed)) S { int a; };',
            ['(tag struct __attribute__((packed)) S { int a })'],
        ];
        yield 'after the body of a structure' => [
            'struct S { int a; } __attribute__((packed));',
            ['(tag struct __attribute__((packed)) S { int a })'],
        ];
        yield 'around the declarators of members' => [
            'struct S { int a : 3 __attribute__((x)), __attribute__((y)) b; int : 2 __attribute__((z)); };',
            ['(tag struct S { int a:3 __attribute__((x)); int b __attribute__((y)); int _:2 __attribute__((z)) })'],
        ];
        yield 'after the body of an enumeration' => [
            'enum E { A } __attribute__((packed));',
            ['(tag enum __attribute__((packed)) E { A })'],
        ];
        yield 'after enum without a body' => [
            'enum __attribute__((packed)) E;',
            ['(tag enum __attribute__((packed)) E)'],
        ];
        yield 'after an enumerator' => [
            'enum E { A __attribute__((deprecated)) = 1 };',
            ['(tag enum E { A __attribute__((deprecated))=1 })'],
        ];
        yield 'start of a parenthesized declarator' => [
            'int (__attribute__((x)) *p);',
            ['(variable (ptr (attr __attribute__((x)) int)) p)'],
        ];
        yield 'start of a parenthesized declarator of a function' => [
            'typedef void (__attribute__((x)) *F)(int);',
            ['(typedef typedef (ptr (fn void (int) __attribute__((x)))) F)'],
        ];
        yield 'start of a parenthesized name of a function' => [
            'int (__attribute__((a)) f)(void);',
            ['(function (fn int (void) __attribute__((a))) f)'],
        ];
        yield 'start of a parenthesized array' => [
            'int (__attribute__((a)) x[3]);',
            ['(variable (array (attr __attribute__((a)) int) 3) x)'],
        ];
        yield 'start of a parenthesized name of a function definition' => [
            'int (__attribute__((a)) f)(void) { return 0; }',
            ['(definition (fn int (void) __attribute__((a))) f { return 0; })'],
        ];
        yield 'start of a parenthesized declarator of a parameter' => [
            'void f(int (__attribute__((x)) *p));',
            ['(function (fn void ((ptr (attr __attribute__((x)) int)) p)) f)'],
        ];
        yield 'start of a parenthesized abstract declarator' => [
            'int s = sizeof(int (__attribute__((x)) *));',
            ['(variable int s = (sizeof (ptr (attr __attribute__((x)) int))))'],
        ];
        yield 'parenthesized attributes alone in a type name' => [
            'int s = sizeof(int (__attribute__((x))));',
            ['(variable int s = (sizeof (fn int (__attribute__((x)) _))))'],
        ];
        yield 'parameter of an abstract function declarator' => [
            'void f(int (__attribute__((x)) int));',
            ['(function (fn void ((fn int (__attribute__((x)) int)))) f)'],
        ];
        yield 'in the brackets of an array' => [
            'void f(int a[static __attribute__((x)) const 3]);',
            ['(function (fn void ((array int static const 3 __attribute__((x))) a)) f)'],
        ];
        yield 'in the brackets of an array before the attributes after them' => [
            'int a[__attribute__((x)) 3] [[y]];',
            ['(variable (array int 3 __attribute__((x)) [[y]]) a)'],
        ];
        yield 'in a type name' => [
            'int a = sizeof(__attribute__((x)) int);',
            ['(variable int a = (sizeof {__attribute__((x)) int}))'],
        ];
        yield 'alone' => ['__attribute__((x));', ['(attributes __attribute__((x)))']];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('placesDataProvider')]
    public function testAttributeIsParsedInPlace(string $code, array $expected): void
    {
        self::assertSame($expected, \array_map(NodePrinter::print(...), self::parse($code)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function listsDataProvider(): iterable
    {
        yield 'empty list' => ['int f(void) __attribute__(());', '(function (fn int (void)) f)'];
        yield 'empty attributes in the list' => [
            'int f(void) __attribute__((a, , b,));',
            '(function (fn int (void)) f __attribute__((a)) __attribute__((b)))',
        ];
        yield 'keyword as a name' => [
            'int f(void) __attribute__((const));',
            '(function (fn int (void)) f __attribute__((const)))',
        ];
        yield 'version of availability' => [
            'int f(void) __attribute__((availability(macos, introduced=10.13.4)));',
            '(function (fn int (void)) f __attribute__((availability(macos, (tokens introduced=10.13.4)))))',
        ];
        yield 'arguments' => [
            'int f(const char *, ...) __attribute__((format(printf, 1, 2)));',
            '(function (fn int ((ptr [const char]), ...)) f __attribute__((format(printf, 1, 2))))',
        ];
    }

    #[DataProvider('listsDataProvider')]
    public function testAttributeListIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(self::parseSingle($code, RootDeclarationInterface::class)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, non-empty-string}>
     */
    public static function invalidSpecifiersDataProvider(): iterable
    {
        yield 'single parenthesis' => ['int a __attribute__(x);', 20, 'x'];
        yield 'unclosed list' => ['int a __attribute__((x);', 23, ';'];
        yield 'between the name and the parameters' => ['int f __attribute__((x)) (void);', 25, '('];
        yield 'before an assembler label' => ['int x __attribute__((x)) __asm__("a");', 25, '__asm__'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidSpecifiersDataProvider')]
    public function testInvalidSpecifierIsRejected(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }
}
