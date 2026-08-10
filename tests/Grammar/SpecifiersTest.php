<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\TypeDeclaration;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks the declaration specifiers of `resources/grammar/specifiers.pp3`. A
 * declaration without declarators is compared as an S-expression:
 * `(type static [const (unsigned int)])` is a declaration of a qualified type
 * of a modified one with the `static` specifier.
 */
#[CoversNothing]
final class SpecifiersTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function typesDataProvider(): iterable
    {
        yield 'void' => ['void', 'void'];
        yield 'char' => ['char', 'char'];
        yield 'signed char' => ['signed char', '(signed char)'];
        yield 'unsigned char' => ['unsigned char', '(unsigned char)'];
        yield 'short' => ['short', '(short _)'];
        yield 'signed short' => ['signed short', '(signed short _)'];
        yield 'short int' => ['short int', '(short int)'];
        yield 'short int in another order' => ['int short', '(short int)'];
        yield 'signed short int' => ['signed short int', '(signed short int)'];
        yield 'unsigned short' => ['unsigned short', '(unsigned short _)'];
        yield 'unsigned short int' => ['unsigned short int', '(unsigned short int)'];
        yield 'int' => ['int', 'int'];
        yield 'signed' => ['signed', '(signed _)'];
        yield 'signed int' => ['signed int', '(signed int)'];
        yield 'unsigned' => ['unsigned', '(unsigned _)'];
        yield 'unsigned int' => ['unsigned int', '(unsigned int)'];
        yield 'long' => ['long', '(long _)'];
        yield 'signed long' => ['signed long', '(signed long _)'];
        yield 'long int' => ['long int', '(long int)'];
        yield 'unsigned long in another order' => ['long unsigned', '(long unsigned _)'];
        yield 'unsigned long int' => ['unsigned long int', '(unsigned long int)'];
        yield 'long long' => ['long long', '(long long _)'];
        yield 'long long int' => ['long long int', '(long long int)'];
        yield 'signed long long' => ['signed long long', '(signed long long _)'];
        yield 'unsigned long long' => ['unsigned long long', '(unsigned long long _)'];
        yield 'unsigned long long int' => ['unsigned long long int', '(unsigned long long int)'];
        yield 'unsigned long long int in reverse' => ['int long long unsigned', '(long long unsigned int)'];
        yield 'base between the modifiers' => ['long unsigned int long', '(long unsigned long int)'];
        yield 'bit-precise integer' => ['_BitInt(8)', '_BitInt(8)'];
        yield 'signed bit-precise integer' => ['signed _BitInt(8)', '(signed _BitInt(8))'];
        yield 'unsigned bit-precise integer' => ['unsigned _BitInt(8)', '(unsigned _BitInt(8))'];
        yield 'float' => ['float', 'float'];
        yield 'double' => ['double', 'double'];
        yield 'long double' => ['long double', '(long double)'];
        yield '_Decimal32' => ['_Decimal32', '_Decimal32'];
        yield '_Decimal64' => ['_Decimal64', '_Decimal64'];
        yield '_Decimal128' => ['_Decimal128', '_Decimal128'];
        yield 'bool' => ['bool', 'bool'];
        yield '_Bool' => ['_Bool', 'bool'];
        yield 'float complex' => ['float _Complex', '(_Complex float)'];
        yield 'double complex' => ['double _Complex', '(_Complex double)'];
        yield 'long double complex' => ['long double _Complex', '(long _Complex double)'];
        yield 'complex in another order' => ['_Complex long double', '(_Complex long double)'];
        yield 'float imaginary' => ['float _Imaginary', '(_Imaginary float)'];
        yield 'double imaginary' => ['double _Imaginary', '(_Imaginary double)'];
        yield 'long double imaginary' => ['long double _Imaginary', '(long _Imaginary double)'];

        yield 'qualifiers' => ['const volatile int', '[const volatile int]'];
        yield 'qualifiers of a modified type' => ['const unsigned int', '[const (unsigned int)]'];
        yield 'qualifiers between the type specifiers' => ['unsigned const long', '[const (unsigned long _)]'];
        yield 'restrict' => ['restrict int', '[restrict int]'];
        yield 'atomic qualifier' => ['_Atomic int', '[_Atomic int]'];
        yield 'atomic qualifier after the base' => ['int _Atomic', '[_Atomic int]'];
        yield 'atomic type specifier' => ['_Atomic(int)', '_Atomic(int)'];
        yield 'atomic type specifier with a space' => ['_Atomic (int)', '_Atomic(int)'];
        yield 'atomic typedef name' => ['_Atomic(T)', '_Atomic(T)'];
        yield 'qualified atomic type specifier' => ['const _Atomic(long long)', '[const _Atomic((long long _))]'];
        yield 'qualifiers only' => ['const', '[const _]'];
        yield 'repeated qualifier' => ['const const int', '[const const int]'];

        yield 'typedef name' => ['T', 'T'];
        yield 'qualified typedef name' => ['const T', '[const T]'];
        yield 'typedef name before the qualifier' => ['T volatile', '[volatile T]'];
        yield 'typedef name before a modifier' => ['T unsigned', '(unsigned T)'];
        yield 'typedef name before a modifier and a qualifier' => ['T long const', '[const (long T)]'];

        yield 'typeof of a type' => ['typeof(int)', 'typeof(int)'];
        yield 'typeof of an identifier' => ['typeof(x)', 'typeof(x)'];
        yield 'typeof of an expression' => ['typeof(x + 1)', 'typeof((+ x 1))'];
        yield 'typeof of a structure' => ['typeof(struct S)', 'typeof(struct S)'];
        yield 'typeof_unqual' => ['typeof_unqual(const int)', 'typeof_unqual([const int])'];
        yield 'typeof_unqual of an expression' => ['typeof_unqual(x + 1)', 'typeof_unqual((+ x 1))'];

        yield 'long char' => ['long char', '(long char)'];
        yield 'long long long' => ['long long long', '(long long long _)'];
        yield 'signed unsigned int' => ['signed unsigned int', '(signed unsigned int)'];
        yield 'short double' => ['short double', '(short double)'];
    }

    #[DataProvider('typesDataProvider')]
    public function testTypeIsParsed(string $specifiers, string $expected): void
    {
        $declaration = self::parseSingle($specifiers . ';', TypeDeclaration::class);

        self::assertSame($expected, NodePrinter::print($declaration->type));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function declarationsDataProvider(): iterable
    {
        // The specifiers that are not a part of the type
        yield 'storage class' => ['static int;', '(type static int)'];
        yield 'every storage class' => [
            'typedef extern static thread_local _Thread_local auto register constexpr int;',
            '(type typedef extern static thread_local thread_local auto register constexpr int)',
        ];
        yield 'storage classes after the type' => ['int static extern;', '(type static extern int)'];
        yield 'repeated typedef' => ['typedef typedef int;', '(type typedef typedef int)'];
        yield 'function specifiers' => ['inline _Noreturn void;', '(type inline _Noreturn void)'];
        yield 'alignment specifiers' => [
            'alignas(16) _Alignas(double) int;',
            '(type alignas(16) alignas(double) int)',
        ];
        yield 'specifiers in the order written' => [
            'const static inline unsigned int;',
            '(type static inline [const (unsigned int)])',
        ];
        yield 'storage class without a type' => ['static;', '(type static _)'];

        // Tags
        yield 'structure reference' => ['struct S;', '(tag struct S)'];
        yield 'structure' => ['struct S { int a; };', '(tag struct S { int a })'];
        yield 'anonymous structure' => ['struct { int a; };', '(tag struct _ { int a })'];
        yield 'union' => ['union U { int a; float b; };', '(tag union U { int a; float b })'];
        yield 'union reference' => ['union U;', '(tag union U)'];
        yield 'several declarators' => ['struct S { int a, b; };', '(tag struct S { int a; int b })'];
        yield 'bit-fields' => [
            'struct S { unsigned a : 3, : 2; };',
            '(tag struct S { (unsigned _) a:3; (unsigned _) _:2 })',
        ];
        yield 'anonymous member' => [
            'struct S { union { int a; float b; }; };',
            '(tag struct S { union _ { int a; float b } _ })',
        ];
        yield 'static assertion among the members' => [
            'struct S { int a; static_assert(1); };',
            '(tag struct S { int a; (static_assert 1) })',
        ];
        yield 'typedef name as a member type' => ['struct S { T a; };', '(tag struct S { T a })'];
        yield 'qualified member' => ['struct S { const char c; };', '(tag struct S { [const char] c })'];
        yield 'named nested structure' => [
            'struct S { struct T { int x; } t; };',
            '(tag struct S { struct T { int x } t })',
        ];
        yield 'member with every specifier' => [
            'struct S { static const int inline alignas(4) a; };',
            '(tag struct S { static inline alignas(4) [const int] a })',
        ];
        yield 'storage class of a tag' => ['static struct S { int a; };', '(tag static struct S { int a })'];
        yield 'enumeration reference' => ['enum E;', '(tag enum E)'];
        yield 'enumeration' => ['enum E { A, B = 2 };', '(tag enum E { A, B=2 })'];
        yield 'trailing comma' => ['enum E { A, };', '(tag enum E { A })'];
        yield 'anonymous enumeration' => ['enum { A };', '(tag enum _ { A })'];
        yield 'underlying type' => ['enum E : unsigned char { A };', '(tag enum E : (unsigned char) { A })'];
        yield 'anonymous enumeration with an underlying type' => [
            'enum : long { A };',
            '(tag enum _ : (long _) { A })',
        ];
        yield 'enumeration declared with its underlying type' => ['enum E : int;', '(tag enum E : int)'];
        yield 'storage class in an underlying type' => ['enum E : static int;', '(tag enum E : {static int})'];
        yield 'alignment specifier in an underlying type' => [
            'enum E : alignas(4) int { A };',
            '(tag enum E : {alignas(4) int} { A })',
        ];

        // A tag wrapped into another type gives the general declaration,
        // even if it declares the tag along the way
        yield 'qualified reference' => ['const struct S;', '(type [const struct S])'];
        yield 'qualified structure' => ['const struct S { int a; };', '(type [const struct S { int a }])'];
        yield 'qualifier after the structure' => ['struct S { int a; } const;', '(type [const struct S { int a }])'];
        yield 'atomic enumeration' => ['_Atomic(enum E);', '(type _Atomic(enum E))'];
    }

    #[DataProvider('declarationsDataProvider')]
    public function testDeclarationIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(self::parseSingle($code, SpecifiedDeclaration::class)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function offsetsDataProvider(): iterable
    {
        yield 'structure' => ['static struct S { int a; };', [
            'TagDeclaration@0', 'SpecifierList@0', 'StaticSpecifier@0', 'StructType@7', 'Identifier@14',
            'MemberDeclarationList@18', 'MemberDeclaration@18', 'SpecifierList@18', 'IntType@18', 'Identifier@22',
        ]];
        yield 'union' => ['union U { float f : 1; };', [
            'TagDeclaration@0', 'SpecifierList@0', 'UnionType@0', 'Identifier@6', 'MemberDeclarationList@10',
            'MemberDeclaration@10', 'SpecifierList@10', 'FloatType@10', 'Identifier@16', 'DecIntLiteral@20',
        ]];
        yield 'enumeration' => ['enum E : char { A = 1, B };', [
            'TagDeclaration@0', 'SpecifierList@0', 'EnumType@0', 'Identifier@5', 'CharType@9', 'EnumeratorList@16',
            'Enumerator@16', 'Identifier@16', 'DecIntLiteral@20', 'Enumerator@23', 'Identifier@23',
        ]];
        yield 'atomic type' => ['const _Atomic(T) volatile;', [
            'TypeDeclaration@0', 'SpecifierList@0', 'QualifiedType@0', 'AtomicType@6', 'NamedType@14',
            'Identifier@14',
        ]];
        yield 'typeof and alignas' => ['alignas(T) typeof(x);', [
            'TypeDeclaration@0', 'SpecifierList@0', 'AlignAsTypeSpecifier@0', 'NamedType@8', 'Identifier@8',
            'TypeOfTypeType@11', 'NamedType@18', 'Identifier@18',
        ]];
        yield 'bit-precise integer' => ['unsigned _BitInt(8);', [
            'TypeDeclaration@0', 'SpecifierList@0', 'ModifiedType@0', 'BitIntType@9', 'DecIntLiteral@17',
        ]];
        yield 'type built where the specifiers start' => ['static const unsigned int;', [
            'TypeDeclaration@0', 'SpecifierList@0', 'StaticSpecifier@0', 'QualifiedType@0', 'ModifiedType@0',
            'IntType@22',
        ]];
        yield 'members of one declaration' => ['struct S { alignas(8) int a, b : 1; };', [
            'TagDeclaration@0', 'SpecifierList@0', 'StructType@0', 'Identifier@7', 'MemberDeclarationList@11',
            'MemberDeclaration@11', 'SpecifierList@11', 'AlignAsSpecifier@11', 'DecIntLiteral@19', 'IntType@22',
            'Identifier@26', 'MemberDeclaration@11', 'SpecifierList@11', 'AlignAsSpecifier@11', 'DecIntLiteral@19',
            'IntType@22', 'Identifier@29', 'DecIntLiteral@33',
        ]];
        yield 'bases' => ['_Bool;', [
            'TypeDeclaration@0', 'SpecifierList@0', 'BoolType@0',
        ]];
    }

    /**
     * Every node of the declaration starts at its first token.
     *
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('offsetsDataProvider')]
    public function testNodesStartAtTheirFirstToken(string $code, array $expected): void
    {
        self::assertSame($expected, self::listOffsets(self::parseSingle($code, SpecifiedDeclaration::class)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>, string}>
     */
    public static function invalidDeclarationsDataProvider(): iterable
    {
        yield 'two bases' => ['int int;', 4, 'int'];
        yield 'two different bases' => ['char double;', 5, 'double'];
        yield 'typedef name after a type specifier' => ['unsigned T;', 9, 'T'];
        yield 'two typedef names' => ['T T;', 2, 'T'];
        yield 'typedef name after a base' => ['int T;', 4, 'T'];
        yield 'typedef name after a modifier' => ['long T;', 5, 'T'];
        yield 'member without a semicolon' => ['struct { int a };', 15, '}'];
        yield 'empty structure' => ['struct S {};', 10, '}'];
        yield 'empty enumeration' => ['enum E {};', 8, '}'];
        yield 'structure without a name and a body' => ['struct;', 6, ';'];
        yield 'union without a name and a body' => ['union;', 5, ';'];
        yield 'enumeration without a name and a body' => ['enum;', 4, ';'];
        yield 'unclosed structure' => ['struct S { int a;', 17, ''];
        yield 'unclosed enumeration' => ['enum { A', 8, ''];
        yield 'enumerator without a name' => ['enum E { = 1 };', 9, '='];
        yield 'enumerators without a comma' => ['enum E { A B };', 11, 'B'];
        yield 'atomic type specifier without a type' => ['_Atomic();', 8, ')'];
        yield 'bit-precise integer without a width' => ['_BitInt();', 8, ')'];
        yield 'typeof without an operand' => ['typeof();', 7, ')'];
        yield 'static assertion member without a semicolon' => ['struct S { static_assert(1) };', 28, '}'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidDeclarationsDataProvider')]
    public function testInvalidDeclarationIsRejected(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }
}
