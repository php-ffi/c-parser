<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Exception\SyntaxErrorException;
use FFI\Parser\Parser;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Messages the grammar gives for the typical mistakes in a header.
 */
#[CoversNothing]
final class SyntaxErrorsTest extends TestCase
{
    /**
     * Invalid headers along with the offset and the text of the fragment the
     * parser has failed at, empty at the end of input, and the message.
     *
     * @return iterable<non-empty-string, array{string, int<0, max>, string, non-empty-string}>
     */
    public static function syntaxErrorsDataProvider(): iterable
    {
        yield 'declaration without a semicolon' => [
            "int x\nint y;",
            6,
            'int',
            'Syntax error, unexpected "int" (T_INT) in the declaration, ";" expected',
        ];
        yield 'declaration without a semicolon at the end of input' => [
            'int x',
            5,
            '',
            'Syntax error, unexpected end of input in the declaration, ";" expected',
        ];
        yield 'unknown character' => [
            'int x @ 3;',
            6,
            '@',
            'Syntax error, unexpected "@" (unknown token) in the declaration, ";" expected',
        ];
        yield 'excess parenthesis' => [
            'int f(void));',
            11,
            ')',
            'Syntax error, unexpected ")" (T_RPAREN) in the declaration, ";" expected',
        ];
        yield 'unclosed structure' => [
            "struct S {\n  int a;\nint f(void);",
            32,
            '',
            'Syntax error, unexpected end of input in the members, "}" expected',
        ];
        yield 'member without a semicolon' => [
            'struct S { int a int b; };',
            17,
            'int',
            'Syntax error, unexpected "int" (T_INT) in the member declaration, ";" expected',
        ];
        yield 'unclosed enumeration' => [
            'enum E { A, B',
            13,
            '',
            'Syntax error, unexpected end of input in the enumerators, "," or "}" expected',
        ];
        yield 'enumerators without a comma' => [
            'enum E { A B };',
            11,
            'B',
            'Syntax error, unexpected "B" (T_IDENTIFIER) in the enumerators, "," or "}" expected',
        ];
        yield 'unclosed parameters' => [
            'int f(int a, char b;',
            19,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the parameters, "," or ")" expected',
        ];
        yield 'parameter without a type' => [
            'int f(int a, );',
            13,
            ')',
            'Syntax error, unexpected ")" (T_RPAREN) in the parameters, "," or ")" expected',
        ];
        yield 'missing element of an initializer list' => [
            'int a[] = {1, , 2};',
            14,
            ',',
            'Syntax error, unexpected "," (T_COMMA) in the initializer list, "," or "}" expected',
        ];
        yield 'invalid number in an initializer' => [
            'int x = 08;',
            8,
            '08',
            'Syntax error, unexpected "08" (T_PP_NUMBER) in the initializer',
        ];
        yield 'unclosed string in an initializer' => [
            'const char *s = "abc;',
            16,
            '"abc;',
            'Syntax error, unexpected "\\"abc;" (T_INVALID_STRING_LITERAL) in the initializer',
        ];
        yield 'invalid number in an expression' => [
            'int x = 1 + 0xG;',
            12,
            '0xG',
            'Syntax error, unexpected "0xG" (T_PP_NUMBER) in the expression',
        ];
        yield 'subscript of a sizeof expression' => [
            'int x = sizeof(int)[0];',
            19,
            '[',
            'Syntax error, unexpected "[" (T_LBRACKET) in the expression',
        ];
        yield 'invalid character constant in a cast' => [
            "static_assert((int)'ab);",
            19,
            "'ab);",
            'Syntax error, unexpected "\'ab);" (T_INVALID_CHAR_LITERAL) in the expression',
        ];
        yield 'token that cannot start a declaration' => [
            'int x[];]',
            8,
            ']',
            'Syntax error, unexpected "]" (T_RBRACKET), a declaration expected',
        ];
        yield 'pointer without a declarator' => [
            'int *;',
            5,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the declaration',
        ];
        yield 'missing declarator after a comma' => [
            'int a, ;',
            7,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the declarator',
        ];
        yield 'unclosed parenthesized declarator' => [
            'int (x;',
            6,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the declarator, ")" expected',
        ];
        yield 'unclosed array declarator' => [
            'int x[3;',
            7,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the array declarator, "]" expected',
        ];
        yield 'unclosed function body' => [
            'int f() { return 0;',
            19,
            '',
            'Syntax error, unexpected end of input in the compound statement, "}" expected',
        ];
        yield 'assembler label without parentheses' => [
            'int x __asm__;',
            13,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the assembler label, "(" expected',
        ];
        yield 'assembler label without a string' => [
            'int x __asm__(a);',
            14,
            'a',
            'Syntax error, unexpected "a" (T_IDENTIFIER) in the assembler label, a string literal expected',
        ];
        yield 'unclosed assembler label' => [
            'int x __asm__("a";',
            17,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the assembler label, ")" expected',
        ];
        yield 'static assertion without parentheses' => [
            'static_assert 1;',
            14,
            '1',
            'Syntax error, unexpected "1" (T_DEC_INT_LITERAL) in the static assertion, "(" expected',
        ];
        yield 'static assertion without a comma' => [
            'static_assert(1 "a");',
            16,
            '"a"',
            'Syntax error, unexpected "\"a\"" (T_STRING_LITERAL) in the static assertion, "," or ")" expected',
        ];
        yield 'static assertion with a message that is not a string' => [
            'static_assert(1, 2);',
            17,
            '2',
            'Syntax error, unexpected "2" (T_DEC_INT_LITERAL) in the static assertion, a string literal expected',
        ];
        yield 'static assertion without a semicolon' => [
            'static_assert(1, "a")',
            21,
            '',
            'Syntax error, unexpected end of input after the static assertion, ";" expected',
        ];
        yield 'structure without a tag and a body' => [
            'struct;',
            6,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the structure specifier, a tag or "{" expected',
        ];
        yield 'union without a tag and a body' => [
            'union;',
            5,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the union specifier, a tag or "{" expected',
        ];
        yield 'enumeration without a tag and a body' => [
            'enum;',
            4,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the enumeration specifier, a tag, ":" or "{" expected',
        ];
        yield 'enumeration without enumerators' => [
            'enum E {};',
            8,
            '}',
            'Syntax error, unexpected "}" (T_RBRACE) in the enumerators, an enumerator expected',
        ];
        yield 'enumeration without an underlying type' => [
            'enum E : { A };',
            9,
            '{',
            'Syntax error, unexpected "{" (T_LBRACE) in the enumeration specifier, an underlying type expected',
        ];
        yield 'atomic type specifier without a type' => [
            '_Atomic();',
            8,
            ')',
            'Syntax error, unexpected ")" (T_RPAREN) in the atomic type specifier, a type name expected',
        ];
        yield 'unclosed atomic type specifier' => [
            '_Atomic(int x;',
            12,
            'x',
            'Syntax error, unexpected "x" (T_IDENTIFIER) in the atomic type specifier, ")" expected',
        ];
        yield 'bit-precise integer type without a width' => [
            '_BitInt x;',
            8,
            'x',
            'Syntax error, unexpected "x" (T_IDENTIFIER) in the bit-precise integer type, "(" expected',
        ];
        yield 'unclosed bit-precise integer type' => [
            '_BitInt(8 x;',
            10,
            'x',
            'Syntax error, unexpected "x" (T_IDENTIFIER) in the bit-precise integer type, ")" expected',
        ];
        yield 'empty typeof specifier' => [
            'typeof() x;',
            7,
            ')',
            'Syntax error, unexpected ")" (T_RPAREN) in the typeof specifier, a type name or an expression expected',
        ];
        yield 'unclosed typeof specifier' => [
            'typeof(int x;',
            11,
            'x',
            'Syntax error, unexpected "x" (T_IDENTIFIER) in the typeof specifier, ")" expected',
        ];
        yield 'alignment specifier without parentheses' => [
            'alignas int x;',
            8,
            'int',
            'Syntax error, unexpected "int" (T_INT) in the alignment specifier, "(" expected',
        ];
        yield 'unclosed alignment specifier' => [
            'alignas(8 int x;',
            10,
            'int',
            'Syntax error, unexpected "int" (T_INT) in the alignment specifier, ")" expected',
        ];
        yield 'unclosed parenthesized expression' => [
            'int x = (1 + 2;',
            14,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the parenthesized expression, ")" expected',
        ];
        yield 'conditional expression without a colon' => [
            'int x = 1 ? 2;',
            13,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the conditional expression, ":" expected',
        ];
        yield 'unclosed subscript' => [
            'int x = a[1;',
            11,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the subscript, "]" expected',
        ];
        yield 'arguments of a call without a comma' => [
            'int x = f(1 2);',
            12,
            '2',
            'Syntax error, unexpected "2" (T_DEC_INT_LITERAL) in the arguments, "," or ")" expected',
        ];
        yield 'member access without a member' => [
            'int x = a->1;',
            11,
            '1',
            'Syntax error, unexpected "1" (T_DEC_INT_LITERAL) in the member access, a member name expected',
        ];
        yield 'generic selection without associations' => [
            'int x = _Generic(1);',
            18,
            ')',
            'Syntax error, unexpected ")" (T_RPAREN) in the generic selection, "," expected',
        ];
        yield 'generic association without a colon' => [
            'int x = _Generic(1, int);',
            23,
            ')',
            'Syntax error, unexpected ")" (T_RPAREN) in the generic association, ":" expected',
        ];
        yield 'unclosed generic selection' => [
            'int x = _Generic(1, int: 1;',
            26,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the generic selection, "," or ")" expected',
        ];
        yield 'unclosed arguments of a built-in function' => [
            'int x = __builtin_offsetof(struct S, a;',
            38,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the arguments, "," or ")" expected',
        ];
        yield 'unclosed statement expression' => [
            'int x = ({ 1; );',
            14,
            ')',
            'Syntax error, unexpected ")" (T_RPAREN) in the compound statement, "}" expected',
        ];
        yield 'designated initializer without an assignment' => [
            'struct S s = {.a 1};',
            17,
            '1',
            'Syntax error, unexpected "1" (T_DEC_INT_LITERAL) in the designated initializer, "=" expected',
        ];
        yield 'member designator without a member' => [
            'struct S s = {. = 1};',
            16,
            '=',
            'Syntax error, unexpected "=" (T_ASSIGN) in the designator, a member name expected',
        ];
        yield 'unclosed index designator' => [
            'int a[] = {[1 = 2};',
            14,
            '=',
            'Syntax error, unexpected "=" (T_ASSIGN) in the designator, "]" or "..." expected',
        ];
        yield 'unclosed standard attribute specifier' => [
            '[[deprecated int x;',
            13,
            'int',
            'Syntax error, unexpected "int" (T_INT) in the attribute specifier, "," or "]]" expected',
        ];
        yield 'standard attribute without a name after the prefix' => [
            '[[a::]] int x;',
            5,
            ']',
            'Syntax error, unexpected "]" (T_RBRACKET) in the attribute, a name after "::" expected',
        ];
        yield 'unclosed arguments of an attribute' => [
            '[[a::b(1]] int x;',
            8,
            ']',
            'Syntax error, unexpected "]" (T_RBRACKET) in the attribute arguments, ")" expected',
        ];
        yield 'attribute specifier of GCC with a single parenthesis' => [
            '__attribute__(a)) int x;',
            14,
            'a',
            'Syntax error, unexpected "a" (T_IDENTIFIER) in the attribute specifier, "((" expected',
        ];
        yield 'unclosed attribute specifier of GCC' => [
            '__attribute__((a) int x;',
            18,
            'int',
            'Syntax error, unexpected "int" (T_INT) in the attribute specifier, "))" expected',
        ];
        yield 'unclosed attribute specifier of MSVC' => [
            '__declspec(a int x;',
            18,
            ';',
            'Syntax error, unexpected ";" (T_SEMICOLON) in the __declspec specifier, ")" expected',
        ];
        yield 'pointer modifier without arguments' => [
            'int * __ptrauth p;',
            16,
            'p',
            'Syntax error, unexpected "p" (T_IDENTIFIER) in the pointer modifier, "(" expected',
        ];
    }

    /**
     * @param int<0, max> $offset
     * @param non-empty-string $message
     */
    #[DataProvider('syntaxErrorsDataProvider')]
    public function testSyntaxErrorIsReportedWithItsMessage(
        string $code,
        int $offset,
        string $fragment,
        string $message,
    ): void {
        self::assertSyntaxError($code, $offset, $fragment);

        try {
            (new Parser())->parse($code);
        } catch (SyntaxErrorException $e) {
            self::assertSame($message, $e->getMessage());

            return;
        }

        self::fail('SyntaxErrorException has not been thrown');
    }
}
