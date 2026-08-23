<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\VariableDeclaration;
use FFI\Parser\Node\Type\PointerType;
use FFI\Parser\Tests\NodePrinter;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks the external declarations of `resources/grammar/declarations.pp3`:
 * a header is compared as the list of its declarations printed as
 * S-expressions, `(variable static (ptr int) p = 0)`.
 */
#[CoversNothing]
final class DeclarationsTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function declarationsDataProvider(): iterable
    {
        yield 'static assertion without a message' => ['static_assert(a);', ['(static_assert a@14)@0']];
        yield 'static assertion with a message' => [
            'static_assert(a, "message");',
            ['(static_assert a@14 "message"@17)@0'],
        ];
        yield 'static assertion with adjacent strings' => [
            'static_assert(a, "mes" "sage");',
            ['(static_assert a@14 (concat "mes"@17 "sage"@23)@17)@0'],
        ];
        yield 'underscored spelling of static_assert' => ['_Static_assert(a);', ['(static_assert a@15)@0']];
        yield 'declarations in order' => [
            "; static_assert(a);\n;; static_assert(b, \"b\");",
            ['(static_assert a@16)@2', '(static_assert b@37 "b"@40)@23'],
        ];
        yield 'tag declaration' => ['static struct S;', ['(tag static@0 struct S@7)@0']];
        yield 'type declaration' => ['const T;', ['(type [const T@6]@0)@0']];
        yield 'variable' => ['int a = 1;', ['(variable int@0 a@4 = 1@8)@0']];
        yield 'attribute declaration' => ['[[a]]; int x;', ['(attributes [[a@2]]@2)@0', '(variable int@7 x@11)@7']];
        yield 'attributes and a storage class' => ['[[a]] static;', ['(type [[a@2]]@2 static@6 _)@0']];
        yield 'static assertion and a variable' => [
            'static_assert(1); int x;',
            ['(static_assert 1@14)@0', '(variable int@18 x@22)@18'],
        ];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('declarationsDataProvider')]
    public function testDeclarationsAreParsed(string $code, array $expected): void
    {
        self::assertSame($expected, \array_map(NodePrinter::printWithOffsets(...), self::parse($code)));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function declaratorDeclarationsDataProvider(): iterable
    {
        // The kind of the declaration
        yield 'variable' => ['extern const char *const names[16];', [
            '(variable extern (array [const (ptr [const char])] 16) names)',
        ]];
        yield 'function prototype' => ['int printf(const char *restrict fmt, ...);', [
            '(function (fn int ([restrict (ptr [const char])] fmt, ...)) printf)',
        ]];
        yield 'function with a qualified return type' => ['const int f(void);', [
            '(function (fn [const int] (void)) f)',
        ]];
        yield 'function returning a pointer to a function' => ['void (*signal(int, void (*)(int)))(int);', [
            '(function (fn (ptr (fn void (int))) (int, (ptr (fn void (int))))) signal)',
        ]];
        yield 'typedef of a pointer to a function' => ['typedef void (*callback_t)(void *ctx, int code);', [
            '(typedef typedef (ptr (fn void ((ptr void) ctx, int code))) callback_t)',
        ]];
        yield 'typedef of an anonymous structure' => ['typedef struct { int x, y; } point_t;', [
            '(typedef typedef struct _ { int x; int y } point_t)',
        ]];
        yield 'typedef of a function type' => ['typedef int F(void);', ['(typedef typedef (fn int (void)) F)']];
        yield 'typedef after the type' => ['int typedef T;', ['(typedef typedef int T)']];
        yield 'typedef after a structure' => ['struct S { int a; } typedef T;', [
            '(typedef typedef struct S { int a } T)',
        ]];
        yield 'parenthesized function name' => ['int (f)(void);', ['(function (fn int (void)) f)']];
        yield 'pointer to a function is a variable' => ['int (*f)(void);', ['(variable (ptr (fn int (void))) f)']];
        yield 'name of a typedef function type is a variable' => ['F f;', ['(variable F f)']];
        yield 'name of a typeof function type is a variable' => ['typeof(int (void)) f;', [
            '(variable typeof((fn int (void))) f)',
        ]];

        // Several declarators
        yield 'several declarators' => ['int a, *b, c[3];', [
            '(variable int a)',
            '(variable (ptr int) b)',
            '(variable (array int 3) c)',
        ]];
        yield 'functions and variables in one declaration' => ['int f(void), g, *h(int);', [
            '(function (fn int (void)) f)',
            '(variable int g)',
            '(function (fn (ptr int) (int)) h)',
        ]];
        yield 'several typedef names' => ['typedef int A, *B, C[3];', [
            '(typedef typedef int A)',
            '(typedef typedef (ptr int) B)',
            '(typedef typedef (array int 3) C)',
        ]];
        yield 'typedef names of a function and a pointer' => ['typedef void F(void), *G;', [
            '(typedef typedef (fn void (void)) F)',
            '(typedef typedef (ptr void) G)',
        ]];

        // Tags with declarators are objects
        yield 'structure defined in a declaration' => ['struct S { int a; } a, *b;', [
            '(variable struct S { int a } a)',
            '(variable (ptr struct S { int a }) b)',
        ]];
        yield 'structure reference' => ['struct S a, b;', ['(variable struct S a)', '(variable struct S b)']];
        yield 'anonymous structure' => ['struct { int a; } x;', ['(variable struct _ { int a } x)']];
        yield 'union' => ['union U { int a; } u;', ['(variable union U { int a } u)']];
        yield 'enumeration' => ['enum E { A, B } e;', ['(variable enum E { A, B } e)']];
        yield 'enumeration with an underlying type' => ['enum E : int e;', ['(variable enum E : int e)']];
        yield 'atomic type' => ['_Atomic(int) x;', ['(variable _Atomic(int) x)']];

        // The typedef name rule
        yield 'name after a modifier' => ['unsigned T;', ['(variable (unsigned _) T)']];
        yield 'name after a typedef name' => ['T T;', ['(variable T T)']];
        yield 'name after a base' => ['int T;', ['(variable int T)']];
        yield 'name after a modifier and a base' => ['unsigned int T;', ['(variable (unsigned int) T)']];
        yield 'parenthesized name without a type' => ['static (x);', ['(variable static _ x)']];
        yield 'name before an initializer without a type' => ['static x = 1;', ['(variable static _ x = 1)']];
        yield 'inferred type' => ['constexpr auto n = 1;', ['(variable constexpr auto _ n = 1)']];

        // Initializers
        yield 'constant expression' => ['constexpr int N = 10;', ['(variable constexpr int N = 10)']];
        yield 'conditional expression' => ['int x = a ? b : c;', ['(variable int x = (? a b c))']];
        yield 'parenthesized comma expression' => ['int x = (1, 2);', ['(variable int x = (, 1 2))']];
        yield 'string' => ['char s[] = "abc";', ['(variable (array char) s = "abc")']];
        yield 'list with a trailing comma' => ['int a[] = {1, 2, 3,};', ['(variable (array int) a = {1, 2, 3})']];
        yield 'empty list' => ['int a[3] = {};', ['(variable (array int 3) a = {})']];
        yield 'empty nested list' => ['int a[1] = {{}};', ['(variable (array int 1) a = {{}})']];
        yield 'nested lists' => ['int m[2][2] = {{1, 2}, [1] = {3}};', [
            '(variable (array (array int 2) 2) m = {{1, 2}, [1]={3}})',
        ]];
        yield 'designators of members and elements' => ['struct P p = {.a.b[2].c = 1, 2};', [
            '(variable struct P p = {.a.b[2].c=1, 2})',
        ]];
        yield 'designators of elements' => ['int a[2][3] = {[1][2] = 3};', [
            '(variable (array (array int 3) 2) a = {[1][2]=3})',
        ]];
        yield 'designator in a designated list' => ['struct S s = {.a = {.b = 1}};', [
            '(variable struct S s = {.a={.b=1}})',
        ]];
        yield 'initializers of several declarators' => ['int a = 1, b, c = {2};', [
            '(variable int a = 1)',
            '(variable int b)',
            '(variable int c = {2})',
        ]];

        // Compound literals
        yield 'compound literal of an array' => ['int *p = (int[]){1, 2};', [
            '(variable (ptr int) p = (compound (array int) {1, 2}))',
        ]];
        yield 'address of a compound literal' => ['const struct S *p = &(struct S){1};', [
            '(variable (ptr [const struct S]) p = (&u (compound struct S {1})))',
        ]];
        yield 'compound literal of a typedef name' => ['int x = (T){1};', ['(variable int x = (compound T {1}))']];
        yield 'size of a compound literal' => ['int x = sizeof (int){1};', [
            '(variable int x = (sizeof (compound int {1})))',
        ]];
        yield 'member of a compound literal' => ['int x = (struct S){1}.a;', [
            '(variable int x = (. (compound struct S {1}) a))',
        ]];
        yield 'compound literal with a storage class' => ['int x = (static int){1};', [
            '(variable int x = (compound {static int} {1}))',
        ]];

        // Function definitions, the body keeps its text
        yield 'function definition' => ['int f(void) { return 0; }', [
            '(definition (fn int (void)) f { return 0; })',
        ]];
        yield 'static inline function of glibc' => [
            "static __inline __uint16_t\n__bswap_16 (__uint16_t __bsx)\n{\n  return __builtin_bswap16 (__bsx);\n}",
            ['(definition static inline (fn __uint16_t (__uint16_t __bsx)) __bswap_16 '
                . "{\n  return __builtin_bswap16 (__bsx);\n})"],
        ];
        yield 'nested braces, strings and assembler in a body' => [
            'void f(void) { if (x) { g("}"); } __asm__ volatile ("nop"); }',
            ['(definition (fn void (void)) f { if (x) { g("}"); } __asm__ volatile ("nop"); })'],
        ];
        yield 'empty body' => ['void f() {}', ['(definition (fn void ()) f {})']];
        yield 'definition with attributes' => ['__attribute__((always_inline)) int f(int x) [[a]] { return x; }', [
            '(definition __attribute__((always_inline)) (fn int (int x) [[a]]) f { return x; })',
        ]];
        yield 'definitions and declarations' => ['int f(void) { return 1; } int g(void);', [
            '(definition (fn int (void)) f { return 1; })',
            '(function (fn int (void)) g)',
        ]];
        yield 'body after a declarator of no function' => ['int x { 1 }', ['(definition int x { 1 })']];
        yield 'body after an initializer' => ['int x = {1} {2}', ['(definition int x = {1} {2})']];

        // Extensions of GCC
        yield 'assembler label' => ['int f(void) __asm__("_f");', ['(function (fn int (void)) f asm("_f"))']];
        yield 'assembler label of adjacent strings' => ['extern int x __asm("_" "x") = 1;', [
            '(variable extern int x asm((concat "_" "x")) = 1)',
        ]];
        yield 'assembler labels of several declarators' => ['int a __asm__("a"), b __asm__("b");', [
            '(variable int a asm("a"))',
            '(variable int b asm("b"))',
        ]];
        yield 'assembler label of a typedef' => ['typedef int T __asm__("t");', ['(typedef typedef int T asm("t"))']];
        yield 'range designator' => ['int a[] = {[0 ... 3] = 1, [4 ... 5].x = 2};', [
            '(variable (array int) a = {[0 ... 3]=1, [4 ... 5].x=2})',
        ]];
        yield 'marker of the extensions before a declaration' => ['__extension__ typedef long long T;', [
            '(typedef typedef (long long _) T)',
        ]];

        // Semantically invalid, but syntactically valid
        yield 'typedef with an initializer' => ['typedef int x = 1;', ['(typedef typedef int x = 1)']];
        yield 'typedef with another storage class' => ['typedef static int x = 1;', [
            '(typedef typedef static int x = 1)',
        ]];
        yield 'repeated typedef' => ['typedef typedef int T;', ['(typedef typedef typedef int T)']];
        yield 'typedef without a type' => ['typedef (x);', ['(typedef typedef _ x)']];
        yield 'function with an initializer' => ['int f(void) = 1;', ['(function (fn int (void)) f = 1)']];
        yield 'two storage classes' => ['static extern int x;', ['(variable static extern int x)']];
        yield 'typedef without a declarator' => ['typedef int;', ['(type typedef int)']];
        yield 'declaration of nothing' => ['int;', ['(type int)']];
    }

    /**
     * @param list<non-empty-string> $expected
     */
    #[DataProvider('declaratorDeclarationsDataProvider')]
    public function testDeclaratorDeclarationsAreParsed(string $code, array $expected): void
    {
        self::assertSame($expected, \array_map(NodePrinter::print(...), self::parse($code)));
    }

    public function testDeclaratorsOfOneDeclarationShareTheSpecifiers(): void
    {
        [$a, $b] = self::parse('static struct S { int a; } a, *b;');

        self::assertInstanceOf(VariableDeclaration::class, $a);
        self::assertInstanceOf(VariableDeclaration::class, $b);
        self::assertInstanceOf(PointerType::class, $b->type);
        self::assertSame($a->type, $b->type->type);
        self::assertSame($a->specifiers, $b->specifiers);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, list<non-empty-string>}>
     */
    public static function offsetsDataProvider(): iterable
    {
        yield 'variable and function' => ['static int a = 1, *f(void);', [
            'VariableDeclaration@0', 'SpecifierList@0', 'StaticSpecifier@0', 'Identifier@11', 'DecIntLiteral@15',
            'IntType@7', 'FunctionDeclaration@0', 'SpecifierList@0', 'StaticSpecifier@0', 'Identifier@19',
            'FunctionType@20', 'PointerType@18', 'IntType@7', 'ParameterList@21', 'Parameter@21', 'SpecifierList@21',
            'VoidType@21',
        ]];
        yield 'typedef' => ['typedef int T;', [
            'TypeDefDeclaration@0', 'SpecifierList@0', 'TypeDefSpecifier@0', 'Identifier@12', 'IntType@8',
        ]];
        yield 'specifiers after the type' => ['int typedef T;', [
            'TypeDefDeclaration@0', 'SpecifierList@4', 'TypeDefSpecifier@4', 'Identifier@12', 'IntType@0',
        ]];
        yield 'compound literal' => ['int x = (int[]){1};', [
            'VariableDeclaration@0', 'SpecifierList@0', 'Identifier@4', 'CompoundLiteralExpression@8',
            'IncompleteArrayType@12', 'IntType@9', 'InitializerList@15', 'DecIntLiteral@16', 'IntType@0',
        ]];
        yield 'assembler label and a range designator' => ['int a[] __asm__("x") = {[0 ... 1] = 2};', [
            'VariableDeclaration@0', 'SpecifierList@0', 'Identifier@4', 'AsmLabel@8', 'StringLiteral@16',
            'InitializerList@23', 'DesignatedInitializer@24', 'DesignatorList@24', 'RangeDesignator@24',
            'OctIntLiteral@25', 'DecIntLiteral@31', 'DecIntLiteral@36', 'IncompleteArrayType@5', 'IntType@0',
        ]];
        yield 'function definition' => ['static int f() { }', [
            'FunctionDefinition@0', 'SpecifierList@0', 'StaticSpecifier@0', 'Identifier@11', 'FunctionType@12',
            'IntType@7', 'ParameterList@12', 'FunctionBody@15',
        ]];
        yield 'initializer list' => ['int a[] = {[0] = 1, .b.c = {}};', [
            'VariableDeclaration@0', 'SpecifierList@0', 'Identifier@4', 'InitializerList@10',
            'DesignatedInitializer@11', 'DesignatorList@11', 'IndexDesignator@11', 'OctIntLiteral@12',
            'DecIntLiteral@17', 'DesignatedInitializer@20', 'DesignatorList@20', 'MemberDesignator@20', 'Identifier@21',
            'MemberDesignator@22', 'Identifier@23', 'InitializerList@27', 'IncompleteArrayType@5', 'IntType@0',
        ]];
    }

    /**
     * Every declaration starts at its first specifier, and every node of an
     * initializer at its first token.
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
    public static function invalidDeclarationsDataProvider(): iterable
    {
        yield 'missing semicolon' => ['static_assert(a)', 16, ''];
        yield 'missing condition' => ['static_assert();', 14, ')'];
        yield 'message is not a string' => ['static_assert(a, 1);', 17, '1'];
        yield 'two messages' => ['static_assert(a, "b", "c");', 20, ','];
        yield 'unclosed parenthesis' => ['static_assert(a;', 15, ';'];
        yield 'missing parentheses' => ['static_assert a;', 14, 'a'];

        yield 'declaration without a semicolon' => ['int x', 5, ''];
        yield 'unclosed function body' => ['int f(void) { return 0;', 23, ''];
        yield 'unbalanced function body' => ['int f(void) { g(); ) }', 19, ')'];
        yield 'body after a declarator but the first' => ['int a, f(void) { }', 15, '{'];
        yield 'body after a semicolon' => ['int f(void); { }', 13, '{'];
        yield 'missing initializer' => ['int x = ;', 8, ';'];
        yield 'missing initializer of a typedef' => ['typedef int T = ;', 16, ';'];
        yield 'unclosed initializer list' => ['int a[] = {1, 2;', 15, ';'];
        yield 'comma without initializers' => ['int a = {,};', 9, ','];
        yield 'two trailing commas' => ['int a = {1,,};', 11, ','];
        yield 'initializers without a comma' => ['int a = {1 2};', 11, '2'];
        yield 'constant instead of a declarator after a comma' => ['int a = 1, 2;', 11, '2'];
        yield 'assignment as an initializer' => ['int x = a = 1;', 10, '='];
        yield 'comma without a declarator' => ['int x, ;', 7, ';'];
        yield 'comma before the declarators' => ['int , x;', 4, ','];
        yield 'two lists' => ['int x = {1}, {2};', 13, '{'];
        yield 'designator of a number' => ['int x = {.1 = 2};', 12, '='];
        yield 'designator without an index' => ['int x = {[] = 2};', 10, ']'];
        yield 'unclosed compound literal' => ['int x = (int){1;', 15, ';'];
        yield 'designator without an initializer' => ['int a = {[1]};', 12, '}'];
        yield 'designator without an equals sign' => ['int a = {.a 1};', 12, '1'];
        yield 'range without the last index' => ['int a[] = {[0 ...] = 1};', 17, ']'];
        yield 'range of three indices' => ['int a[] = {[0 ... 1 ... 2] = 1};', 20, '...'];
        yield 'name without a type specifier before an array' => ['static x[3];', 8, '['];
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
