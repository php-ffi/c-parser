<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\TagDeclaration;
use FFI\Parser\Node\Declaration\StaticAssertDeclaration;
use FFI\Parser\Node\Type\PointerType;
use FFI\Parser\Node\Type\RecordType;
use FFI\Parser\Node\Type\RecordType\MemberDeclaration;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks the declarators and the type names of
 * `resources/grammar/declarators.pp3`. A declarator is checked as a member
 * of a structure, which needs no other node around it, and a type name as
 * the operand of `sizeof`. The declared type is compared as an S-expression:
 * `(ptr (fn int (int)))` is a pointer to a function.
 */
#[CoversNothing]
final class DeclaratorsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function membersDataProvider(): iterable
    {
        yield 'name' => ['int a;', 'int a'];
        yield 'parenthesized name' => ['int (a);', 'int a'];
        yield 'typedef name and a parenthesized name' => ['T (a);', 'T a'];
        yield 'pointer' => ['int *p;', '(ptr int) p'];
        yield 'pointer to a pointer' => ['int **p;', '(ptr (ptr int)) p'];
        yield 'qualified pointers' => ['char *const *restrict p;', '[restrict (ptr [const (ptr char)])] p'];
        yield 'qualifiers of a pointer in the order written' => [
            'int *volatile const p;',
            '[volatile const (ptr int)] p',
        ];
        yield 'atomic pointer' => ['int *_Atomic p;', '[_Atomic (ptr int)] p'];
        yield 'pointer to a qualified type' => ['const int *p;', '(ptr [const int]) p'];

        // Arrays
        yield 'array' => ['int a[3];', '(array int 3) a'];
        yield 'array of arrays' => ['int a[2][3];', '(array (array int 3) 2) a'];
        yield 'array of pointers' => ['int *a[3];', '(array (ptr int) 3) a'];
        yield 'pointer to an array' => ['int (*a)[3];', '(ptr (array int 3)) a'];
        yield 'flexible array member' => ['int a[];', '(array int) a'];
        yield 'array of unspecified size' => ['int a[*];', '(array int *) a'];
        yield 'size that starts with an asterisk' => ['int a[*p];', '(array int (*u p)) a'];
        yield 'size expression' => ['int a[N * 2 + 1];', '(array int (+ (* N 2) 1)) a'];
        yield 'static size' => ['int a[static 3];', '(array int static 3) a'];
        yield 'qualifiers and a static size' => ['int a[static const 3];', '(array int static const 3) a'];
        yield 'static size after the qualifiers' => ['int a[const static 3];', '(array int static const 3) a'];
        yield 'qualifiers and a size' => ['int a[const volatile 3];', '(array int const volatile 3) a'];
        yield 'qualifiers without a size' => ['int a[restrict];', '(array int restrict) a'];
        yield 'qualifiers and an unspecified size' => ['int a[const *];', '(array int const *) a'];
        yield 'volatile size' => ['int a[volatile 1];', '(array int volatile 1) a'];
        yield 'restrict without a size' => ['int a[restrict];', '(array int restrict) a'];
        yield 'atomic unspecified size' => ['int a[_Atomic *];', '(array int _Atomic *) a'];

        // Functions
        yield 'function without parameters' => ['int f();', '(fn int ()) f'];
        yield 'function with void' => ['int f(void);', '(fn int (void)) f'];
        yield 'variadic function without parameters' => ['int f(...);', '(fn int (...)) f'];
        yield 'variadic function' => ['int f(const char *s, ...);', '(fn int ((ptr [const char]) s, ...)) f'];
        yield 'unnamed parameters' => ['int f(int, char *);', '(fn int (int, (ptr char))) f'];
        yield 'typedef name as a parameter' => ['int f(T);', '(fn int (T)) f'];
        yield 'typedef name and a parameter name' => ['int f(T a);', '(fn int (T a)) f'];
        yield 'parameter name after a type specifier' => ['int f(int T);', '(fn int (int T)) f'];
        yield 'parenthesized parameter name' => ['int f(int (T));', '(fn int (int T)) f'];
        yield 'specifiers of a parameter' => ['int f(register int a);', '(fn int (register int a)) f'];
        yield 'array parameter' => ['int f(int a[static 4]);', '(fn int ((array int static 4) a)) f'];
        yield 'abstract array parameter' => ['int f(char *[]);', '(fn int ((array (ptr char)))) f'];
        yield 'abstract pointer to an array' => ['int f(int (*)[3]);', '(fn int ((ptr (array int 3)))) f'];
        yield 'abstract function parameter' => ['int f(int (int));', '(fn int ((fn int (int)))) f'];
        yield 'function returning a pointer' => ['char *f(void);', '(fn (ptr char) (void)) f'];
        yield 'pointer to a function' => ['int (*f)(int);', '(ptr (fn int (int))) f'];
        yield 'pointer to a function returning a pointer to an array' => [
            'int (*(*f)(void))[3];',
            '(ptr (fn (ptr (array int 3)) (void))) f',
        ];
        yield 'pointer to an array of pointers to functions' => [
            'int (*(*x)[4])(void);',
            '(ptr (array (ptr (fn int (void))) 4)) x',
        ];
        yield 'signal' => [
            'void (*signal(int, void (*)(int)))(int);',
            '(fn (ptr (fn void (int))) (int, (ptr (fn void (int))))) signal',
        ];

        yield 'array of pointers to arrays' => ['int (*a[3])[4];', '(array (ptr (array int 4)) 3) a'];
        yield 'unnamed parameter of a pointer to a function' => [
            'int f(int (*)(int (*)(char)));',
            '(fn int ((ptr (fn int ((ptr (fn int (char)))))))) f',
        ];
        yield 'parameter of a function type with a name' => ['int f(int g(int));', '(fn int ((fn int (int)) g)) f'];
        yield 'non-ASCII names' => [
            "int f(\xC3\x84 a, char *\xC3\x96);",
            "(fn int (\xC3\x84 a, (ptr char) \xC3\x96)) f",
        ];

        // Bit-fields
        yield 'bit-field' => ['int a : 3;', 'int a:3'];
        yield 'unnamed bit-field' => ['int : 3;', 'int _:3'];
        yield 'pointer bit-field' => ['int *a : 3;', '(ptr int) a:3'];
        yield 'parenthesized bit-field name' => ['int (a) : 3;', 'int a:3'];
        yield 'unnamed bit-field after a pointer' => ['int *a, : 3;', '(ptr int) a int _:3'];

        // Semantically invalid, but syntactically valid
        yield 'function returning an array' => ['int f()[3];', '(fn (array int 3) ()) f'];
        yield 'function returning a function' => ['int f()(int);', '(fn (fn int (int)) ()) f'];
        yield 'array of functions' => ['int a[3](int);', '(array (fn int (int)) 3) a'];
        yield 'void among other parameters' => ['int f(void, int);', '(fn int (void, int)) f'];
        yield 'pointer without a type' => ['static *p;', 'static (ptr _) p'];
        yield 'parameter without a type' => ['int f(register);', '(fn int (register _)) f'];
        yield 'any specifier of a parameter' => [
            'int f(static int a, inline int b, typedef int c);',
            '(fn int (static int a, inline int b, typedef int c)) f',
        ];
        yield 'function bit-field' => ['int f(void) : 3;', '(fn int (void)) f:3'];
    }

    #[DataProvider('membersDataProvider')]
    public function testMemberDeclaratorIsParsed(string $member, string $expected): void
    {
        $declaration = self::parseSingle('struct S { ' . $member . ' };', TagDeclaration::class);

        self::assertInstanceOf(RecordType::class, $declaration->type);
        self::assertNotNull($declaration->type->members);
        self::assertSame($expected, NodePrinter::print($declaration->type->members));
    }

    public function testDeclaratorsOfOneMemberDeclarationShareTheSpecifiers(): void
    {
        $declaration = self::parseSingle('struct S { static int a, *b; };', TagDeclaration::class);

        self::assertInstanceOf(RecordType::class, $declaration->type);
        self::assertNotNull($declaration->type->members);

        [$a, $b] = $declaration->type->members->items;

        self::assertInstanceOf(MemberDeclaration::class, $a);
        self::assertInstanceOf(MemberDeclaration::class, $b);
        self::assertInstanceOf(PointerType::class, $b->type);
        self::assertSame($a->type, $b->type->type);
        self::assertSame($a->specifiers, $b->specifiers);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function typeNamesDataProvider(): iterable
    {
        yield 'specifiers' => ['const int', '[const int]'];
        yield 'pointer' => ['int *', '(ptr int)'];
        yield 'qualified pointer' => ['int *const', '[const (ptr int)]'];
        yield 'pointer to an atomic pointer' => ['int *_Atomic *', '(ptr [_Atomic (ptr int)])'];
        yield 'array' => ['int [3]', '(array int 3)'];
        yield 'array without a size' => ['int []', '(array int)'];
        yield 'array of unspecified size' => ['int [*]', '(array int *)'];
        yield 'array with a static size' => ['int [static 3]', '(array int static 3)'];
        yield 'array of arrays without a size' => ['int [][3]', '(array (array int 3))'];
        yield 'pointer to an array' => ['int (*)[3]', '(ptr (array int 3))'];
        yield 'function' => ['int (int)', '(fn int (int))'];
        yield 'function without parameters' => ['int ()', '(fn int ())'];
        yield 'function with void' => ['int (void)', '(fn int (void))'];
        yield 'variadic function without parameters' => ['int (...)', '(fn int (...))'];
        yield 'pointer to a function' => ['void (*)(int, ...)', '(ptr (fn void (int, ...)))'];
        yield 'parenthesized pointer' => ['int ((*))', '(ptr int)'];
        yield 'parenthesized function' => ['int ((int))', '(fn int (int))'];
        yield 'typedef name and a pointer' => ['T *', '(ptr T)'];
        yield 'other specifiers around the specifiers' => ['alignas(8) int *', '(ptr {alignas(8) int})'];
    }

    #[DataProvider('typeNamesDataProvider')]
    public function testTypeNameIsParsed(string $typeName, string $expected): void
    {
        $declaration = self::parseSingle('static_assert(sizeof(' . $typeName . '));', StaticAssertDeclaration::class);

        self::assertSame('(sizeof ' . $expected . ')', NodePrinter::print($declaration->condition));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function specifiersWithTypeNamesDataProvider(): iterable
    {
        yield 'typeof' => ['typeof(int (*)[3]);', '(type typeof((ptr (array int 3))))'];
        yield 'typeof_unqual' => ['typeof_unqual(int *const);', '(type typeof_unqual([const (ptr int)]))'];
        yield 'alignas' => ['alignas(int *) int;', '(type alignas((ptr int)) int)'];
        yield 'atomic type specifier' => ['_Atomic(int (*)(void));', '(type _Atomic((ptr (fn int (void)))))'];
        yield 'typeof of an array without a size' => ['typeof(T[]);', '(type typeof((array T)))'];

        // A type name that reads as an expression is the expression, where
        // the expression is allowed too
        yield 'typeof of a subscript' => ['typeof(T[3]);', '(type typeof(([] T 3)))'];
        yield 'typeof of a product' => ['typeof(a * (b));', '(type typeof((* a b)))'];
        yield 'typeof_unqual of a subscript' => ['typeof_unqual(T[3]);', '(type typeof_unqual(([] T 3)))'];
        yield 'alignas of a subscript' => ['alignas(T[3]) int;', '(type alignas(([] T 3)) int)'];
        yield 'alignas of a product' => ['alignas(a * (b)) int;', '(type alignas((* a b)) int)'];
        yield 'atomic array of a typedef name' => ['_Atomic(T[3]);', '(type _Atomic((array T 3)))'];
    }

    /**
     * Every specifier with a type name accepts an abstract declarator.
     */
    #[DataProvider('specifiersWithTypeNamesDataProvider')]
    public function testTypeNameOfSpecifierIsParsed(string $code, string $expected): void
    {
        self::assertSame($expected, NodePrinter::print(self::parseSingle($code, SpecifiedDeclaration::class)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function typeNamesReadAsExpressionsDataProvider(): iterable
    {
        yield 'parenthesized subscript' => ['(a[1]) - 1', '(- ([] a 1) 1)'];
        yield 'parenthesized subscript of a dereference' => ['(a[*p]) - 1', '(- ([] a (*u p)) 1)'];
        yield 'parenthesized product' => ['(a * (b)) + 1', '(+ (* a b) 1)'];
        yield 'parenthesized product of a parenthesized operand' => ['(a * ((b))) + 1', '(+ (* a b) 1)'];
        yield 'parenthesized product of a comma expression' => ['(a * (b, c)) + 1', '(+ (* a (, b c)) 1)'];
        yield 'parenthesized product of a subscript' => ['(a * (b[1])) + 1', '(+ (* a ([] b 1)) 1)'];
        yield 'parenthesized product of a product' => ['(a * (b * c)) + 1', '(+ (* a (* b c)) 1)'];
        yield 'sizeof of a subscript' => ['sizeof(x[0])', '(sizeof ([] x 0))'];
        yield 'sizeof of an array of a typedef name' => ['sizeof(T[3])', '(sizeof ([] T 3))'];
        yield 'sizeof of a product' => ['sizeof(a * (b))', '(sizeof (* a b))'];
        yield 'sizeof of a product of a dereference' => ['sizeof(a ** (b))', '(sizeof (* a (*u b)))'];
        yield 'alignof of an array of a typedef name' => ['alignof(T[3])', '(alignof ([] T 3))'];
    }

    /**
     * A type name that starts with an identifier and reads as an expression
     * as well is read as the expression, where the expression is allowed.
     */
    #[DataProvider('typeNamesReadAsExpressionsDataProvider')]
    public function testTypeNameThatReadsAsAnExpressionIsReadAsTheExpression(string $code, string $expected): void
    {
        $declaration = self::parseSingle('static_assert(' . $code . ');', StaticAssertDeclaration::class);

        self::assertSame($expected, NodePrinter::print($declaration->condition));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function typeNamesReadAsTypesDataProvider(): iterable
    {
        yield 'cast to a pointer to a typedef name' => ['(T *)x', '(cast (ptr T) x)'];
        yield 'cast of a negative value to a pointer' => ['(T *)-1', '(cast (ptr T) (-u 1))'];
        yield 'cast to a pointer to a function' => ['(T *(*)(int))x', '(cast (ptr (fn (ptr T) (int))) x)'];
        yield 'cast to an array' => ['(int [3])x', '(cast (array int 3) x)'];
        yield 'sizeof of a pointer to a typedef name' => ['sizeof(T *)', '(sizeof (ptr T))'];
        yield 'sizeof of an array without a size' => ['sizeof(T[])', '(sizeof (array T))'];
        yield 'sizeof of an array with a static size' => ['sizeof(T[static 3])', '(sizeof (array T static 3))'];
        yield 'sizeof of an array with qualifiers' => ['sizeof(T[const 3])', '(sizeof (array T const 3))'];
        yield 'sizeof of an array of unspecified size' => ['sizeof(T[*])', '(sizeof (array T *))'];
        yield 'sizeof of a function with a pointer parameter' => [
            'sizeof(T *(U *))',
            '(sizeof (fn (ptr T) ((ptr U))))',
        ];
        yield 'sizeof of a function with a named parameter' => ['sizeof(T *(U x))', '(sizeof (fn (ptr T) (U x)))'];
        yield 'atomic array of a typedef name' => ['sizeof(_Atomic(T[3]))', '(sizeof _Atomic((array T 3)))'];
    }

    /**
     * A type name that cannot be an expression stays a type name, and so
     * does any type name where only a type name is allowed.
     */
    #[DataProvider('typeNamesReadAsTypesDataProvider')]
    public function testTypeNameThatCannotBeAnExpressionIsReadAsTheType(string $code, string $expected): void
    {
        $declaration = self::parseSingle('static_assert(' . $code . ');', StaticAssertDeclaration::class);

        self::assertSame($expected, NodePrinter::print($declaration->condition));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function offsetsDataProvider(): iterable
    {
        yield 'pointer' => ['struct S { int *const p; };', [
            'TagDeclaration@0', 'SpecifierList@0', 'StructType@0', 'Identifier@7', 'MemberDeclarationList@11',
            'MemberDeclaration@11', 'SpecifierList@11', 'QualifiedType@15', 'PointerType@15', 'IntType@11',
            'Identifier@22',
        ]];
        yield 'array' => ['struct S { int a[static 3]; };', [
            'TagDeclaration@0', 'SpecifierList@0', 'StructType@0', 'Identifier@7', 'MemberDeclarationList@11',
            'MemberDeclaration@11', 'SpecifierList@11', 'SizedArrayType@16', 'IntType@11', 'DecIntLiteral@24',
            'Identifier@15',
        ]];
        yield 'array without a size and a parenthesized pointer' => ['struct S { int a[], (*p); };', [
            'TagDeclaration@0', 'SpecifierList@0', 'StructType@0', 'Identifier@7', 'MemberDeclarationList@11',
            'MemberDeclaration@11', 'SpecifierList@11', 'IncompleteArrayType@16', 'IntType@11', 'Identifier@15',
            'MemberDeclaration@11', 'SpecifierList@11', 'PointerType@21', 'IntType@11', 'Identifier@22',
        ]];
        yield 'array of unspecified size' => ['struct S { int f(int b[*]); };', [
            'TagDeclaration@0', 'SpecifierList@0', 'StructType@0', 'Identifier@7', 'MemberDeclarationList@11',
            'MemberDeclaration@11', 'SpecifierList@11', 'FunctionType@16', 'IntType@11', 'ParameterList@17',
            'Parameter@17', 'SpecifierList@17', 'UnspecifiedSizeArrayType@22', 'IntType@17', 'Identifier@21',
            'Identifier@15',
        ]];
        yield 'function' => ['struct S { int f(char c, ...); };', [
            'TagDeclaration@0', 'SpecifierList@0', 'StructType@0', 'Identifier@7', 'MemberDeclarationList@11',
            'MemberDeclaration@11', 'SpecifierList@11', 'FunctionType@16', 'IntType@11', 'ParameterList@17',
            'Parameter@17', 'SpecifierList@17', 'CharType@17', 'Identifier@22', 'Identifier@15',
        ]];
        yield 'empty parameter list' => ['struct S { int f(); };', [
            'TagDeclaration@0', 'SpecifierList@0', 'StructType@0', 'Identifier@7', 'MemberDeclarationList@11',
            'MemberDeclaration@11', 'SpecifierList@11', 'FunctionType@16', 'IntType@11', 'ParameterList@16',
            'Identifier@15',
        ]];
        yield 'type name' => ['static_assert(sizeof(int (*)[2]));', [
            'StaticAssertDeclaration@0', 'SizeOfTypeExpression@14', 'PointerType@26', 'SizedArrayType@28',
            'IntType@21', 'DecIntLiteral@29',
        ]];
    }

    /**
     * A type built by a declarator starts at its own token: the asterisk,
     * the bracket or the parenthesis.
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
    public static function invalidDeclaratorsDataProvider(): iterable
    {
        yield 'ellipsis before a parameter' => ['struct S { int f(..., int); };', 20, ','];
        yield 'trailing comma in parameters' => ['struct S { int f(int,); };', 21, ')'];
        yield 'two ellipses' => ['struct S { int f(int, ..., ...); };', 25, ','];
        yield 'ellipsis without a comma' => ['struct S { int f(int ...); };', 21, '...'];
        yield 'comma without parameters' => ['struct S { int f(,); };', 17, ','];
        yield 'trailing comma in declarators' => ['struct S { int a,; };', 17, ';'];
        yield 'comma expression as a size' => ['struct S { int a[3,4]; };', 18, ','];
        yield 'qualifier after static and the qualifiers' => [
            'struct S { int a[const static restrict 3]; };',
            30,
            'restrict',
        ];
        yield 'atomic type specifier after a pointer' => ['struct S { int *_Atomic(p); };', 16, '_Atomic'];
        yield 'abstract pointer to a function member' => ['struct S { int (*)(void); };', 17, ')'];
        yield 'unclosed parameters' => ['struct S { int f(int; };', 20, ';'];
        yield 'unclosed parenthesized declarator' => ['struct S { int (*p; };', 18, ';'];
        // The error is reported after "[", where an attribute would need
        // the second "["
        yield 'array before the name' => ['struct S { int [3] x; };', 16, '3'];
        yield 'static without a size' => ['struct S { int a[static]; };', 23, ']'];
        yield 'static with an unspecified size' => ['struct S { int a[static *]; };', 25, ']'];
        yield 'two static' => ['struct S { int a[static const static 3]; };', 30, 'static'];
        yield 'abstract member' => ['struct S { int *; };', 16, ';'];
        yield 'abstract bit-field' => ['struct S { int * : 3; };', 17, ':'];
        yield 'name in a type name' => ['static_assert(sizeof(int x));', 25, 'x'];

        // A type name that reads as an expression is the expression in a
        // cast, and the cast to an array or a function is lost with it
        yield 'cast to an array of a typedef name' => ['static_assert((T[3])x);', 20, 'x'];
        yield 'cast to a function of a typedef name' => ['static_assert((T *(U))x);', 22, 'x'];
    }

    /**
     * @param int<0, max> $offset
     */
    #[DataProvider('invalidDeclaratorsDataProvider')]
    public function testInvalidDeclaratorIsRejected(string $code, int $offset, string $fragment): void
    {
        self::assertSyntaxError($code, $offset, $fragment);
    }
}
