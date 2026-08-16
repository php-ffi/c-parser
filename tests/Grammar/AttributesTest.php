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

/**
 * Checks the attributes of `resources/grammar/attributes.pp3` in every
 * syntax and in every position: a header is compared as the list of its
 * declarations printed as S-expressions, where an attribute is printed in
 * its own syntax, `[[gnu::aligned(8)]]`, and the attributes inside a
 * declarator wrap the type, `(attr _Nonnull (ptr int))`.
 */
#[CoversNothing]
final class AttributesTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function syntaxesDataProvider(): iterable
    {
        // C23
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

        // GCC
        yield 'attribute of GCC' => ['__attribute__((packed));', '(attributes __attribute__((packed)))'];
        yield 'short spelling of GCC' => ['__attribute((packed));', '(attributes __attribute__((packed)))'];
        yield 'several attributes of GCC' => [
            '__attribute__((a, b(1)));',
            '(attributes __attribute__((a)) __attribute__((b(1))))',
        ];
        yield 'empty attributes of GCC' => ['__attribute__((, a, ));', '(attributes __attribute__((a)))'];
        yield 'empty specifier of GCC' => ['__attribute__(());', '(attributes )'];
        yield 'calling convention of GCC' => [
            '__attribute__((stdcall, __stdcall__));',
            '(attributes __attribute__((stdcall)) __attribute__((__stdcall__)))',
        ];
        yield 'keyword as a name of GCC' => [
            '__attribute__((const, __const__));',
            '(attributes __attribute__((const)) __attribute__((__const__)))',
        ];

        // MSVC
        yield '__declspec' => ['__declspec(dllimport);', '(attributes __declspec(dllimport))'];
        yield '__declspec of several attributes' => [
            '__declspec(dllimport align(16) noreturn);',
            '(attributes __declspec(dllimport) __declspec(align(16)) __declspec(noreturn))',
        ];
        yield 'empty __declspec' => ['__declspec();', '(attributes )'];

        // Keywords
        foreach ([
            '__cdecl', '__stdcall', '__fastcall', '__thiscall', '__vectorcall', '__ptr32', '__ptr64',
            '_Nonnull', '_Nullable', '_Nullable_result', '_Null_unspecified',
        ] as $keyword) {
            yield 'keyword attribute ' . $keyword => [$keyword . ';', '(attributes ' . $keyword . ')'];
        }

        yield 'syntaxes mixed' => [
            '[[a]] __attribute__((b)) __declspec(c) __stdcall;',
            '(attributes [[a]] __attribute__((b)) __declspec(c) __stdcall)',
        ];
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
        yield 'parenthesized comma expression' => ['a((b, c))', '[[a((, b c))]]'];
        yield 'sizeof of a type' => ['aligned(sizeof(struct S))', '[[aligned((sizeof struct S))]]'];
        yield 'assignment' => [
            'availability(macos, introduced=10.4)',
            '[[availability(macos, (tokens introduced=10.4))]]',
        ];
        yield 'version of the preprocessor number' => [
            'availability(macos, introduced = 10.13.4)',
            '[[availability(macos, (tokens introduced = 10.13.4))]]',
        ];
        yield 'expression followed by tokens' => ['a(1 2)', '[[a((tokens 1 2))]]'];
        yield 'keywords and punctuators' => ['a(int * -> ; :: ...)', '[[a((tokens int * -> ; :: ...))]]'];
        yield 'nested brackets with commas' => ['a([1, 2] {3, (4, 5)})', '[[a((tokens [1, 2] {3, (4, 5)}))]]'];
        yield 'nested parentheses after a name' => ['a(b(c, d), e)', '[[a((tokens b(c, d)), e)]]'];
        yield 'whitespace and comments are kept' => ['a(x  /* y */ = 1)', '[[a((tokens x  /* y */ = 1))]]'];
        yield 'non-ASCII text' => ["a(\"\xC3\xA9\", \\u00E9)", "[[a(\"\xC3\xA9\", \\u00E9)]]"];
        yield 'invalid UTF-8 in a string' => ["a(\"\xFF\")", "[[a(\"\xFF\")]]"];
        yield 'deeply nested tokens' => [
            'a(' . \str_repeat('(', 64) . 'x = 1' . \str_repeat(')', 64) . ')',
            '[[a((tokens ' . \str_repeat('(', 64) . 'x = 1' . \str_repeat(')', 64) . '))]]',
        ];
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
        // Among the specifiers
        yield 'before the specifiers' => ['[[a]] int x;', ['(variable [[a]] int x)']];
        yield 'after the specifiers' => ['int [[a]] x;', ['(variable [[a]] int x)']];
        yield 'among the specifiers' => [
            'static __attribute__((a)) const int __declspec(b) x;',
            ['(variable static __attribute__((a)) __declspec(b) [const int] x)'],
        ];
        yield 'calling convention among the specifiers' => ['void __stdcall f(void);', [
            '(function __stdcall (fn void (void)) f)',
        ]];
        yield 'specifiers of a type name' => ['int x = sizeof(int [[a]]);', [
            '(variable int x = (sizeof {[[a]] int}))',
        ]];

        // A declarator
        yield 'after the name' => ['int x [[a]];', ['(variable int x [[a]])']];
        yield 'after the name and before an array' => ['int x [[a]] [3];', ['(variable (array int 3) x [[a]])']];
        yield 'after the name of a function' => ['int f [[a]] (void);', ['(function (fn int (void)) f [[a]])']];
        yield 'after a function at the end' => ['void exit(int) __attribute__((noreturn));', [
            '(function (fn void (int)) exit __attribute__((noreturn)))',
        ]];
        yield 'C23 attribute after an array at the end' => ['int a[3] [[a]];', [
            '(variable (array int 3 [[a]]) a)',
        ]];
        yield 'attribute of GCC after an array at the end' => ['int a[3] __attribute__((a));', [
            '(variable (array int 3) a __attribute__((a)))',
        ]];
        yield 'C23 attribute after a function followed by a function' => ['int f(int) [[x]] (int);', [
            '(function (fn (fn int (int)) (int) [[x]]) f)',
        ]];
        yield 'C23 attribute after an array followed by a function' => ['int a[2] [[x]] (int);', [
            '(variable (array (fn int (int)) 2 [[x]]) a)',
        ]];
        yield 'after a variable before the initializer' => ['int x __attribute__((aligned(8))) = 1;', [
            '(variable int x __attribute__((aligned(8))) = 1)',
        ]];
        yield 'around the assembler label' => ['int f(void) [[a]] __asm__("_f") [[b]];', [
            '(function (fn int (void) [[a]]) f [[b]] asm("_f"))',
        ]];
        yield 'before a declarator but the first' => ['int a, __attribute__((b)) *c;', [
            '(variable int a)',
            '(variable (ptr int) c __attribute__((b)))',
        ]];
        yield 'in every position of a declarator' => ['int [[a]] x [[b]] __attribute__((c)) = 1;', [
            '(variable [[a]] int x [[b]] __attribute__((c)) = 1)',
        ]];

        // Inside a declarator
        yield 'after a pointer' => ['int * [[a]] p;', ['(variable (ptr int [[a]]) p)']];
        yield 'after a pointer and its qualifiers' => ['int * [[a]] const _Nonnull p;', [
            '(variable [const (ptr int [[a]] _Nonnull)] p)',
        ]];
        yield 'after each pointer' => ['int * _Nonnull * _Nullable p;', [
            '(variable (ptr (ptr int _Nonnull) _Nullable) p)',
        ]];
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
        // A function wrapped into attributes is not told from an object, as
        // a function of a typedef name is not
        yield 'at the start of a parenthesized function declarator' => ['void (__stdcall f)(int);', [
            '(variable (attr __stdcall (fn void (int))) f)',
        ]];
        yield 'after a function in a parenthesized declarator of a pointer' => ['int (*(*p)(void) [[x]])(int);', [
            '(variable (ptr (fn (ptr (fn int (int))) (void) [[x]])) p)',
        ]];
        yield 'at the start of a parenthesized declarator' => ['void (__stdcall *f)(int);', [
            '(variable (ptr (attr __stdcall (fn void (int)))) f)',
        ]];
        yield 'at the start of a parenthesized declarator of GCC' => [
            'void (__attribute__((noreturn)) **f)(void);',
            ['(variable (ptr (ptr (attr __attribute__((noreturn)) (fn void (void))))) f)'],
        ];
        yield 'after the name in a parenthesized declarator' => ['int (*f [[x]])(void);', [
            '(variable (ptr (fn int (void))) f [[x]])',
        ]];
        yield 'typedef of a pointer to a function with a calling convention' => [
            'typedef void (__cdecl *F)(int);',
            ['(typedef typedef (ptr (attr __cdecl (fn void (int)))) F)'],
        ];

        // Parameters
        yield 'parameter' => ['void f([[a]] int x __attribute__((unused)), int y[2] [[b]]);', [
            '(function (fn void ([[a]] int x __attribute__((unused)), (array int 2 [[b]]) y)) f)',
        ]];
        yield 'attributes after arrays and functions of parameters before a comma' => [
            'void f(int y[2] [[b]], int[2] [[c]], int (*g)(void) [[d]], int z);',
            [
                '(function (fn void ((array int 2 [[b]]) y, (array int 2 [[c]]), '
                    . '(ptr (fn int (void) [[d]])) g, int z)) f)',
            ],
        ];
        yield 'attributes of GCC after parameters' => [
            'void f(int y[2] __attribute__((a)), int * __attribute__((b)), int __attribute__((c)));',
            [
                '(function (fn void ((array int 2) y __attribute__((a)), '
                    . '(ptr int __attribute__((b))), __attribute__((c)) int)) f)',
            ],
        ];
        yield 'C23 attribute after an array in a type name' => ['int x = sizeof(int[2] [[x]]);', [
            '(variable int x = (sizeof (array int 2 [[x]])))',
        ]];
        yield 'abstract parameter' => ['void f(int * _Nonnull, int (__stdcall *)(void));', [
            '(function (fn void ((ptr int _Nonnull), (ptr (attr __stdcall (fn int (void)))))) f)',
        ]];
        yield 'type name' => ['int x = sizeof(void (__cdecl *)(void));', [
            '(variable int x = (sizeof (ptr (attr __cdecl (fn void (void))))))',
        ]];

        // Tags
        yield 'after struct' => ['struct [[a]] S;', ['(tag struct [[a]] S)']];
        yield 'after struct and before the body' => ['struct __attribute__((packed)) { int a; } s;', [
            '(variable struct __attribute__((packed)) _ { int a } s)',
        ]];
        yield 'after the body' => ['struct S { int a; } __attribute__((packed, aligned(4)));', [
            '(tag struct __attribute__((packed)) __attribute__((aligned(4))) S { int a })',
        ]];
        yield 'after the body before the declarator' => ['typedef union [[a]] { int a; } [[b]] U;', [
            '(typedef typedef union [[a]] [[b]] _ { int a } U)',
        ]];
        yield 'after the tag without a body' => ['struct S [[a]] s;', ['(variable [[a]] struct S s)']];
        yield 'after the tag of a declaration without a body' => ['struct S [[a]];', ['(tag [[a]] struct S)']];
        yield 'after enum' => ['enum [[a]] E : int { A } __attribute__((packed));', [
            '(tag enum [[a]] __attribute__((packed)) E : int { A })',
        ]];
        yield 'after anonymous enum' => ['enum __attribute__((a)) { A };', ['(tag enum __attribute__((a)) _ { A })']];
        yield 'enumerators' => ['enum E { A [[deprecated]] = 1, B __attribute__((deprecated)), C };', [
            '(tag enum E { A [[deprecated]]=1, B __attribute__((deprecated)), C })',
        ]];

        // Members
        yield 'members' => ['struct S { [[a]] int x [[b]], y __attribute__((c)); };', [
            '(tag struct S { [[a]] int x [[b]]; [[a]] int y __attribute__((c)) })',
        ]];
        yield 'bit-fields' => ['struct S { int x : 3 __attribute__((packed)), : 2 [[a]]; };', [
            '(tag struct S { int x:3 __attribute__((packed)); int _:2 [[a]] })',
        ]];
        yield 'before a member declarator but the first' => ['struct S { int x, __attribute__((a)) y; };', [
            '(tag struct S { int x; int y __attribute__((a)) })',
        ]];
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
        yield 'attribute declaration' => ['[[a(1, b = 2)]] __attribute__(()) [[]];', [
            'AttributeDeclaration@0', 'AttributeList@2', 'StandardAttribute@2', 'Identifier@2', 'ArgumentList@4',
            'DecIntLiteral@4', 'BalancedTokenSequence@7',
        ]];
        yield 'empty attribute declaration' => [' [[]];', ['AttributeDeclaration@1', 'AttributeList@1']];
        yield 'empty attribute declaration of GCC' => [' __attribute__(());', [
            'AttributeDeclaration@1', 'AttributeList@1',
        ]];
        yield 'empty arguments' => ['[[a ()]];', [
            'AttributeDeclaration@0', 'AttributeList@2', 'StandardAttribute@2', 'Identifier@2', 'ArgumentList@4',
        ]];
        yield 'prefixed attribute' => ['[[ gnu :: x ]];', [
            'AttributeDeclaration@0', 'AttributeList@3', 'StandardAttribute@3', 'Identifier@10', 'Identifier@3',
        ]];
        yield 'keyword attribute' => ['int __stdcall *_Nonnull p;', [
            'VariableDeclaration@0', 'SpecifierList@4', 'KeywordAttribute@4', 'Identifier@4', 'Identifier@24',
            'PointerType@14', 'IntType@0', 'AttributeList@15', 'KeywordAttribute@15', 'Identifier@15',
        ]];
        yield 'attributes of a declarator' => ['int f(void) __attribute__((a)) [[b]];', [
            'FunctionDeclaration@0', 'SpecifierList@0', 'Identifier@4', 'AttributeList@27', 'GnuAttribute@27',
            'Identifier@27', 'StandardAttribute@33', 'Identifier@33', 'FunctionType@5', 'IntType@0', 'ParameterList@6',
            'Parameter@6', 'SpecifierList@6', 'VoidType@6',
        ]];
        yield 'attributes after an array' => ['int a[2] [[x]] [3];', [
            'VariableDeclaration@0', 'SpecifierList@0', 'Identifier@4', 'SizedArrayType@5', 'SizedArrayType@15',
            'IntType@0', 'DecIntLiteral@16', 'AttributeList@11', 'StandardAttribute@11', 'Identifier@11',
            'DecIntLiteral@6',
        ]];
        yield 'attributes of a parenthesized declarator' => ['void ( __cdecl *f)(void);', [
            'VariableDeclaration@0', 'SpecifierList@0', 'Identifier@16', 'PointerType@15', 'AttributedType@7',
            'AttributeList@7', 'KeywordAttribute@7', 'Identifier@7', 'FunctionType@18', 'VoidType@0',
            'ParameterList@19', 'Parameter@19', 'SpecifierList@19', 'VoidType@19',
        ]];
        yield 'attributes before and after the specifiers' => ['[[a]] int [[b]] x;', [
            'VariableDeclaration@0', 'SpecifierList@2', 'StandardAttribute@2', 'Identifier@2', 'StandardAttribute@12',
            'Identifier@12', 'Identifier@16', 'IntType@6',
        ]];
        yield '__declspec and the integer types of MSVC' => ['__declspec(align(8)) __int8 a;', [
            'VariableDeclaration@0', 'SpecifierList@11', 'DeclSpecAttribute@11', 'Identifier@11', 'ArgumentList@17',
            'DecIntLiteral@17', 'Identifier@28', 'Int8Type@21',
        ]];
        yield 'integer types of MSVC and GCC' => ['__int16 a; __int32 b; __int64 c; __int128 d;', [
            'VariableDeclaration@0', 'SpecifierList@0', 'Identifier@8', 'Int16Type@0', 'VariableDeclaration@11',
            'SpecifierList@11', 'Identifier@19', 'Int32Type@11', 'VariableDeclaration@22', 'SpecifierList@22',
            'Identifier@30', 'Int64Type@22', 'VariableDeclaration@33', 'SpecifierList@33', 'Identifier@42',
            'Int128Type@33',
        ]];
        yield 'attributes of a tag' => ['struct [[a]] S {} [[b]];', [
            'TagDeclaration@0', 'SpecifierList@0', 'StructType@0', 'Identifier@13', 'MemberDeclarationList@15',
            'AttributeList@9', 'StandardAttribute@9', 'Identifier@9', 'StandardAttribute@20', 'Identifier@20',
        ]];
        yield 'attributes of an enumerator' => ['enum E { A [[a]] };', [
            'TagDeclaration@0', 'SpecifierList@0', 'EnumType@0', 'Identifier@5', 'EnumeratorList@9', 'Enumerator@9',
            'Identifier@9', 'AttributeList@13', 'StandardAttribute@13', 'Identifier@13',
        ]];
    }

    /**
     * Every attribute starts at its prefix or name, and a list of the
     * attributes at its first attribute or, empty in an attribute
     * declaration, where the declaration does.
     *
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
        yield 'empty argument' => ['[[a(1, , 2)]];', 7, ','];
        yield 'missing prefixed name' => ['[[a::]];', 5, ']'];
        yield 'missing prefix' => ['[[::a]];', 2, '::'];
        yield 'two prefixes' => ['[[a::b::c]];', 6, '::'];
        yield 'single parenthesis of GCC' => ['__attribute__(a) int x;', 14, 'a'];
        yield 'single closing parenthesis of GCC' => ['__attribute__((a) int x;', 18, 'int'];
        yield 'attributes of __declspec separated by a comma' => ['__declspec(a, b);', 12, ','];
        yield 'keyword attribute with arguments' => ['__stdcall(1);', 10, '1'];
        yield 'assembler label of a name' => ['int f(void) __asm__(f);', 20, 'f'];
        yield 'assembler label of a member' => ['struct S { int a __asm__("a"); };', 17, '__asm__'];
        yield 'two assembler labels' => ['int f(void) __asm__("a") __asm__("b");', 25, '__asm__'];
        yield 'attributes after struct alone' => ['struct [[a]];', 12, ';'];
        // GCC allows its attributes in a parenthesized declarator only at
        // the start of it
        yield 'attribute of GCC after an array in a parenthesized declarator' => [
            'int (*a[3] __attribute__((x)));',
            11,
            '__attribute__',
        ];
        // Positions C23 has no attributes in
        yield 'attribute in the brackets of an array' => ['int a[[[x]] 3];', 7, '['];
        yield 'attribute after a parenthesized declarator' => ['int (*f) [[x]] (int);', 15, '('];
        yield 'attribute in a subscript' => ['int x = y[[z]];', 10, '['];
        yield 'attribute before a static assertion' => ['[[a]] static_assert(1);', 6, 'static_assert'];
        yield 'attribute before an initializer' => ['int x [[a]] = [[b]] 1;', 14, '['];
        yield 'attributes between the tag and the body' => ['struct S [[a]] { int a; };', 15, '{'];
        yield 'empty last argument' => ['[[a(1,)]];', 6, ')'];
        yield 'keyword of the extensions as a name' => ['__attribute__((__declspec));', 15, '__declspec'];
        yield 'keyword of the extensions as an argument' => ['[[a(__asm__)]];', 4, '__asm__'];
        yield 'extra closing parenthesis of GCC' => ['__attribute__((a))) int x;', 18, ')'];
        yield 'empty assembler label' => ['int f(void) __asm__();', 20, ')'];
        yield 'two integer types' => ['__int128 __int128 x;', 9, '__int128'];
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
