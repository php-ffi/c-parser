<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Node\Attribute\Attribute\ArgumentList\BalancedTokenSequence;
use FFI\Parser\Node\Attribute\StandardAttribute;
use FFI\Parser\Node\Declaration\AttributeDeclaration;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversNothing]
final class AttributesTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function syntaxesDataProvider(): iterable
    {
        yield 'standard attribute' => ['[[deprecated]];', '(attributes [[deprecated]])'];
        yield 'prefixed attribute' => ['[[gnu::packed]];', '(attributes [[gnu::packed]])'];
        yield 'several attributes' => ['[[a, b::c]];', '(attributes [[a]] [[b::c]])'];
        yield 'several specifiers' => ['[[a]] [[b]];', '(attributes [[a]] [[b]])'];
        yield 'empty attributes in the list' => ['[[, a, , b,]];', '(attributes [[a]] [[b]])'];
        yield 'empty specifier' => ['[[]];', '(attributes )'];
        yield 'specifier of empty attributes' => ['[[,]];', '(attributes )'];
        yield 'digraphs' => ['<:<:a:>:>;', '(attributes [[a]])'];
        yield 'keyword as a name' => [
            '[[const, _Noreturn, gnu::const]];',
            '(attributes [[const]] [[_Noreturn]] [[gnu::const]])',
        ];
        yield 'keyword as a prefix' => ['[[int::x]];', '(attributes [[int::x]])'];
        yield 'reserved keyword as a name' => ['[[fallthrough, default]];', '(attributes [[fallthrough]] [[default]])'];

    }

    #[DataProvider('syntaxesDataProvider')]
    public function testAttributeIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(self::parseSingle($code, AttributeDeclaration::class)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function argumentsDataProvider(): iterable
    {
        yield 'empty arguments' => ['a()', '[[a()]]'];
        yield 'constant' => ['aligned(8)', '[[aligned(8)]]'];
        yield 'string' => ['deprecated("use g" " instead")', '[[deprecated((concat "use g" " instead"))]]'];
        yield 'expressions' => ['format(printf, 1, 1 + 1)', '[[format(printf, 1, (+ 1 1))]]'];
        yield 'parenthesized comma expression' => ['a((b, c))', '[[a((paren (, b c)))]]'];
        yield 'sizeof of a type' => ['aligned(sizeof(struct S))', '[[aligned((sizeof struct S))]]'];
        yield 'assignment' => [
            'availability(macos, introduced=10.4)',
            '[[availability(macos, (tokens introduced=10.4))]]',
        ];
        yield 'expression followed by tokens' => ['a(1 2)', '[[a((tokens 1 2))]]'];
        yield 'keywords and punctuators' => ['a(int * -> ; :: ...)', '[[a((tokens int * -> ; :: ...))]]'];
        yield 'nested brackets with commas' => ['a([1, 2] {3, (4, 5)})', '[[a((tokens [1, 2] {3, (4, 5)}))]]'];
        yield 'function call' => ['a(b(c, d), e)', '[[a((call b c d), e)]]'];
        yield 'nested parentheses after a name' => ['a(b(c, d) e)', '[[a((tokens b(c, d) e))]]'];
        yield 'whitespace and comments are kept' => ['a(x  /* y */ = 1)', '[[a((tokens x  /* y */ = 1))]]'];
        yield 'empty argument' => ['a(1, , 2)', '[[a((tokens 1, , 2))]]'];
        yield 'empty last argument' => ['a(1,)', '[[a((tokens 1,))]]'];
        yield 'empty first argument' => ['a(, b)', '[[a((tokens , b))]]'];
        yield 'keyword of an extension as an argument' => ['a(__asm__)', '[[a((tokens __asm__))]]'];
        yield 'non-ASCII text' => ["a(\"\xC3\xA9\", \\u00E9)", "[[a(\"\xC3\xA9\", \\u00E9)]]"];
        yield 'invalid UTF-8 in a string' => ["a(\"\xFF\")", "[[a(\"\xFF\")]]"];
        yield 'deeply nested tokens' => [
            'a(' . \str_repeat('(', 64) . 'x y' . \str_repeat(')', 64) . ')',
            '[[a((tokens ' . \str_repeat('(', 64) . 'x y' . \str_repeat(')', 64) . '))]]',
        ];
        yield 'hash and double hash' => ['a(# x ## y %: %:%:)', '[[a((tokens # x ## y %: %:%:))]]'];
        yield 'two brackets closed one by one' => ['a(x[[1], [2]])', '[[a((tokens x[[1], [2]]))]]'];
    }

    #[DataProvider('argumentsDataProvider')]
    public function testArgumentsAreParsed(string $attribute, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(
            self::parseSingle('[[' . $attribute . ']];', AttributeDeclaration::class)->attributes[0],
        ));
    }

    public function testArgumentIsAnExpressionOrTheTokens(): void
    {
        $attribute = self::parseSingle('[[a(1, b = 2)]];', AttributeDeclaration::class)->attributes[0];

        self::assertInstanceOf(StandardAttribute::class, $attribute);
        self::assertNotNull($attribute->arguments);
        self::assertInstanceOf(ExpressionInterface::class, $attribute->arguments[0]);
        self::assertInstanceOf(BalancedTokenSequence::class, $attribute->arguments[1]);
        self::assertSame('b = 2', $attribute->arguments[1]->raw);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function positionsDataProvider(): iterable
    {
        yield 'before the specifiers' => ['[[a]] int x;', ['(variable [[a]] int x)']];
        yield 'after the specifiers' => ['int [[a]] x;', ['(variable [[a]] int x)']];
        yield 'before and after the specifiers' => ['[[a]] static const int [[b]] x;', [
            '(variable [[a]] static [[b]] [const int] x)',
        ]];
        yield 'specifiers of a type name' => ['int x = sizeof(int [[a]]);', [
            '(variable int x = (sizeof {[[a]] int}))',
        ]];

        yield 'after the name' => ['int x [[a]];', ['(variable int x [[a]])']];
        yield 'after the name and before an array' => ['int x [[a]] [3];', ['(variable (array int 3) x [[a]])']];
        yield 'after the name of a function' => ['int f [[a]] (void);', ['(function (fn int (void)) f [[a]])']];
        yield 'after the name before the initializer' => ['int x [[a]] = 1;', ['(variable int x [[a]] = 1)']];
        yield 'after an array at the end' => ['int a[3] [[a]];', [
            '(variable (array int 3 [[a]]) a)',
        ]];
        yield 'after a function followed by a function' => ['int f(int) [[x]] (int);', [
            '(function (fn (fn int (int)) (int) [[x]]) f)',
        ]];
        yield 'after an array followed by a function' => ['int a[2] [[x]] (int);', [
            '(variable (array (fn int (int)) 2 [[x]]) a)',
        ]];
        yield 'in every position of a declarator' => ['int [[a]] x [[b]] = 1;', [
            '(variable [[a]] int x [[b]] = 1)',
        ]];

        yield 'after a pointer' => ['int * [[a]] p;', ['(variable (ptr int [[a]]) p)']];
        yield 'after a pointer before its qualifiers' => ['int * [[a]] const p;', [
            '(variable [const (ptr int [[a]])] p)',
        ]];
        yield 'after each pointer' => ['int * [[a]] * [[b]] p;', ['(variable (ptr (ptr int [[a]]) [[b]]) p)']];
        yield 'after an array followed by an array' => ['int a[2] [[x]] [3];', [
            '(variable (array (array int 3) 2 [[x]]) a)',
        ]];
        yield 'after a function in a parenthesized declarator' => ['int (*f(void) [[x]])(int);', [
            '(function (fn (ptr (fn int (int))) (void) [[x]]) f)',
        ]];
        yield 'after a function in a parenthesized declarator in order' => [
            'int (*f [[a]] (void) [[b]])(int) [[c]];',
            ['(function (fn (ptr (fn int (int) [[c]])) (void) [[b]]) f [[a]])'],
        ];
        yield 'after a function in a parenthesized declarator of a pointer' => ['int (*(*p)(void) [[x]])(int);', [
            '(variable (ptr (fn (ptr (fn int (int))) (void) [[x]])) p)',
        ]];
        yield 'after the name in a parenthesized declarator' => ['int (*f [[x]])(void);', [
            '(variable (ptr (fn int (void))) f [[x]])',
        ]];

        yield 'parameter' => ['void f([[a]] int x [[b]], int y[2] [[c]]);', [
            '(function (fn void ([[a]] int x [[b]], (array int 2 [[c]]) y)) f)',
        ]];
        yield 'attributes after arrays and functions of parameters before a comma' => [
            'void f(int y[2] [[b]], int[2] [[c]], int (*g)(void) [[d]], int z);',
            [
                '(function (fn void ((array int 2 [[b]]) y, (array int 2 [[c]]), '
                    . '(ptr (fn int (void) [[d]])) g, int z)) f)',
            ],
        ];
        yield 'after an array in a type name' => ['int x = sizeof(int[2] [[x]]);', [
            '(variable int x = (sizeof (array int 2 [[x]])))',
        ]];

        yield 'after struct' => ['struct [[a]] S;', ['(tag struct [[a]] S)']];
        yield 'after struct and before the body' => ['struct [[packed]] { int a; } s;', [
            '(variable struct [[packed]] _ { int a } s)',
        ]];
        yield 'after the body' => ['struct S { int a; } [[b]];', ['(tag [[b]] struct S { int a })']];
        yield 'after union and before the body of a typedef' => ['typedef union [[a]] { int a; } U;', [
            '(typedef typedef union [[a]] _ { int a } U)',
        ]];
        yield 'after the tag without a body' => ['struct S [[a]] s;', ['(variable [[a]] struct S s)']];
        yield 'after the tag of a declaration without a body' => ['struct S [[a]];', ['(tag [[a]] struct S)']];
        yield 'after enum' => ['enum [[a]] E : int { A };', ['(tag enum [[a]] E : int { A })']];
        yield 'after anonymous enum' => ['enum [[a]] { A };', ['(tag enum [[a]] _ { A })']];
        yield 'enumerators' => ['enum E { A [[deprecated]] = 1, B [[deprecated]], C };', [
            '(tag enum E { A [[deprecated]]=1, B [[deprecated]], C })',
        ]];

        yield 'members' => ['struct S { [[a]] int x [[b]], y [[c]]; };', [
            '(tag struct S { [[a]] int x [[b]]; [[a]] int y [[c]] })',
        ]];
        yield 'bit-field' => ['struct S { int x [[a]] : 3, : 2; };', ['(tag struct S { int x:3 [[a]]; int _:2 })']];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('positionsDataProvider')]
    public function testAttributeBelongsToItsNode(string $code, array $expected): void
    {
        self::assertSame($expected, \array_map(NodePrinter::print(...), self::parse($code)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function offsetsDataProvider(): iterable
    {
        yield 'attribute declaration' => ['[[a(1, b = 2)]] [[]];', [
            'AttributeDeclaration@0', 'AttributeList@2', 'StandardAttribute@2', 'Identifier@2', 'ArgumentList@4',
            'DecIntLiteral@4', 'BalancedTokenSequence@7',
        ]];
        yield 'empty attribute declaration' => [' [[]];', ['AttributeDeclaration@1', 'AttributeList@1']];
        yield 'empty arguments' => ['[[a ()]];', [
            'AttributeDeclaration@0', 'AttributeList@2', 'StandardAttribute@2', 'Identifier@2', 'ArgumentList@4',
        ]];
        yield 'prefixed attribute' => ['[[ gnu :: x ]];', [
            'AttributeDeclaration@0', 'AttributeList@3', 'StandardAttribute@3', 'Identifier@10', 'Identifier@3',
        ]];
        yield 'attributes of a declarator' => ['int f [[a]] (void) [[b]];', [
            'FunctionDeclaration@0', 'Identifier@4', 'AttributeList@8', 'StandardAttribute@8', 'Identifier@8',
            'FunctionType@12', 'IntType@0', 'ParameterList@13', 'Parameter@13', 'VoidType@13', 'AttributeList@21',
            'StandardAttribute@21', 'Identifier@21',
        ]];
        yield 'attributes after an array' => ['int a[2] [[x]] [3];', [
            'VariableDeclaration@0', 'Identifier@4', 'SizedArrayType@5', 'SizedArrayType@15',
            'IntType@0', 'DecIntLiteral@16', 'AttributeList@11', 'StandardAttribute@11', 'Identifier@11',
            'DecIntLiteral@6',
        ]];
        yield 'attributes of a pointer' => ['int * [[a]] p;', [
            'VariableDeclaration@0', 'Identifier@12', 'PointerType@4', 'IntType@0', 'AttributeList@8',
            'StandardAttribute@8', 'Identifier@8',
        ]];
        yield 'attributes before and after the specifiers' => ['[[a]] int [[b]] x;', [
            'VariableDeclaration@0', 'SpecifierList@2', 'StandardAttribute@2', 'Identifier@2', 'StandardAttribute@12',
            'Identifier@12', 'Identifier@16', 'IntType@6',
        ]];
        yield 'attributes of a tag' => ['struct [[a]] S { int x; } [[b]];', [
            'TagDeclaration@0', 'SpecifierList@28', 'StandardAttribute@28', 'Identifier@28', 'StructType@0',
            'Identifier@13', 'MemberDeclarationList@17', 'MemberDeclaration@17', 'IntType@17', 'Identifier@21',
            'AttributeList@9', 'StandardAttribute@9', 'Identifier@9',
        ]];
        yield 'attributes of an enumerator' => ['enum E { A [[a]] };', [
            'TagDeclaration@0', 'EnumType@0', 'Identifier@5', 'EnumeratorList@9', 'Enumerator@9',
            'Identifier@9', 'AttributeList@13', 'StandardAttribute@13', 'Identifier@13',
        ]];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('offsetsDataProvider')]
    public function testNodesStartAtTheirFirstToken(string $code, array $expected): void
    {
        self::assertSame($expected, self::listOffsets(self::parse($code)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, string}>
     */
    public static function invalidAttributesDataProvider(): iterable
    {
        yield 'unclosed attribute' => ['[[a int x;', 4, 'int'];
        yield 'single closing bracket' => ['[[a] int x;', 5, 'int'];
        yield 'unbalanced parenthesis in the arguments' => ['[[a(b]] int x;', 5, ']'];
        yield 'unbalanced bracket in the arguments' => ['[[a([b)]] int x;', 6, ')'];
        yield 'missing prefixed name' => ['[[a::]];', 5, ']'];
        yield 'missing prefix' => ['[[::a]];', 2, '::'];
        yield 'two prefixes' => ['[[a::b::c]];', 6, '::'];
        yield 'attributes after struct alone' => ['struct [[a]];', 12, ';'];
        yield 'attributes after enum without a body' => ['enum [[a]] E;', 12, ';'];
        yield 'attribute among the specifiers' => ['int [[a]] const x;', 10, 'const'];
        yield 'attribute between the specifiers and a typedef name' => ['static [[a]] int x;', 13, 'int'];
        yield 'attribute before a declarator but the first' => ['int a, [[b]] c;', 7, '[['];
        yield 'attribute after the width of a bit-field' => ['struct S { int x : 3 [[a]]; };', 21, '[['];
        yield 'attribute after the qualifiers of a pointer' => ['int * const [[a]] p;', 12, '[['];
        yield 'attribute in the brackets of an array' => ['int a[[[x]] 3];', 7, '['];
        yield 'attribute among the qualifiers of an array' => ['int a[const [[x]] 3];', 12, '[['];
        yield 'attribute after a parenthesized declarator' => ['int (*f) [[x]] (int);', 9, '[['];
        yield 'attribute before a type name' => ['int x = sizeof([[a]] int);', 15, '[['];
        yield 'attribute in a subscript' => ['int x = y[[z]];', 9, '[['];
        yield 'attribute before a static assertion' => ['[[a]] static_assert(1);', 6, 'static_assert'];
        yield 'attribute before an initializer' => ['int x [[a]] = [[b]] 1;', 14, '[['];
        yield 'attributes between the tag and the body' => ['struct S [[a]] { int a; };', 15, '{'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidAttributesDataProvider')]
    public function testInvalidAttributeIsRejected(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }
}
