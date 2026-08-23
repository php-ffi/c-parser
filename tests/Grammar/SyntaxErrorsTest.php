<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Exception\SyntaxException;
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
        } catch (SyntaxException $e) {
            self::assertSame($message, $e->getMessage());

            return;
        }

        self::fail('SyntaxException has not been thrown');
    }
}
