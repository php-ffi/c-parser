<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Tests\TestCase;
use Phplrt\Compiler\Compiler;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\Transformer\RuntimeLexerTransformer;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks the lexemes of `resources/grammar/lexemes.pp3`. The lexer is built
 * from the grammar, the generated parser is kept equal to it by CI
 * (`composer grammar:build` and `git diff`).
 */
#[CoversNothing]
final class LexemesTest extends TestCase
{
    private const GRAMMAR = __DIR__ . '/../../resources/grammar.pp3';

    private static LexerInterface $lexer;

    /**
     * @return iterable<non-empty-string, array{string, list<array{string, string}>}>
     */
    public static function tokensDataProvider(): iterable
    {
        $keywords = [
            'T_ALIGNAS' => ['alignas', '_Alignas'],
            'T_ALIGNOF' => ['alignof', '_Alignof'],
            'T_AUTO' => ['auto'],
            'T_BOOL' => ['bool', '_Bool'],
            'T_CHAR' => ['char'],
            'T_CONST' => ['const'],
            'T_CONSTEXPR' => ['constexpr'],
            'T_DOUBLE' => ['double'],
            'T_ENUM' => ['enum'],
            'T_EXTERN' => ['extern'],
            'T_FALSE' => ['false'],
            'T_FLOAT' => ['float'],
            'T_INLINE' => ['inline'],
            'T_INT' => ['int'],
            'T_LONG' => ['long'],
            'T_NULLPTR' => ['nullptr'],
            'T_REGISTER' => ['register'],
            'T_RESTRICT' => ['restrict'],
            'T_SHORT' => ['short'],
            'T_SIGNED' => ['signed'],
            'T_SIZEOF' => ['sizeof'],
            'T_STATIC_ASSERT' => ['static_assert', '_Static_assert'],
            'T_STATIC' => ['static'],
            'T_STRUCT' => ['struct'],
            'T_THREAD_LOCAL' => ['thread_local', '_Thread_local'],
            'T_TRUE' => ['true'],
            'T_TYPEDEF' => ['typedef'],
            'T_TYPEOF_UNQUAL' => ['typeof_unqual'],
            'T_TYPEOF' => ['typeof'],
            'T_UNION' => ['union'],
            'T_UNSIGNED' => ['unsigned'],
            'T_VOID' => ['void'],
            'T_VOLATILE' => ['volatile'],
            'T_ATOMIC' => ['_Atomic'],
            'T_BITINT' => ['_BitInt'],
            'T_COMPLEX' => ['_Complex'],
            'T_DECIMAL128' => ['_Decimal128'],
            'T_DECIMAL32' => ['_Decimal32'],
            'T_DECIMAL64' => ['_Decimal64'],
            'T_IMAGINARY' => ['_Imaginary'],
            'T_NORETURN' => ['_Noreturn'],
            'T_RESERVED_KEYWORD' => [
                'break', 'case', 'continue', 'default', 'do', 'else', 'for',
                'goto', 'if', 'return', 'switch', 'while', '_Generic',
            ],
        ];

        foreach ($keywords as $name => $spellings) {
            foreach ($spellings as $spelling) {
                yield 'keyword ' . $spelling => [$spelling, [[$name, $spelling]]];
            }
        }

        yield 'keyword followed by a letter' => ['intA', [['T_IDENTIFIER', 'intA']]];
        yield 'keyword followed by an underscore' => ['int_', [['T_IDENTIFIER', 'int_']]];
        yield 'keyword followed by a digit' => ['int1', [['T_IDENTIFIER', 'int1']]];
        yield 'keyword followed by a non-ASCII letter' => ["int\xC3\x84", [['T_IDENTIFIER', "int\xC3\x84"]]];
        yield 'keyword followed by a universal character name' => ['int\\u00C4', [['T_IDENTIFIER', 'int\\u00C4']]];
        yield 'keyword followed by a punctuator' => ['int*', [['T_INT', 'int'], ['T_ASTERISK', '*']]];
        yield 'keyword followed by a character constant' => [
            "sizeof'a'",
            [['T_SIZEOF', 'sizeof'], ['T_CHAR_LITERAL', "'a'"]],
        ];
        yield 'keyword followed by a floating constant' => [
            'sizeof.5f',
            [['T_SIZEOF', 'sizeof'], ['T_DEC_FLOAT_LITERAL', '.5f']],
        ];
        yield 'longer keyword with a common prefix' => ['typeof_unqual typeof', [
            ['T_TYPEOF_UNQUAL', 'typeof_unqual'],
            ['T_TYPEOF', 'typeof'],
        ]];

        yield 'identifier' => ['point_t', [['T_IDENTIFIER', 'point_t']]];
        yield 'identifier starting with an underscore' => ['__x', [['T_IDENTIFIER', '__x']]];
        yield 'identifier with digits' => ['x86_64', [['T_IDENTIFIER', 'x86_64']]];
        yield 'identifier in UTF-8' => ["caf\xC3\xA9", [['T_IDENTIFIER', "caf\xC3\xA9"]]];
        yield 'identifier of universal character names' => ['\\u00E9t\\U0001F600', [
            ['T_IDENTIFIER', '\\u00E9t\\U0001F600'],
        ]];
        yield 'compiler extension keyword' => ['__attribute__', [['T_IDENTIFIER', '__attribute__']]];

        $constants = [
            'T_DEC_INT_LITERAL' => [
                '42', "1'000'000", '1u', '1U', '1l', '1L', '1ll', '1LL', '1ul', '1lu', '1ULL', '1llu', '1wb', '1WB',
                '1uwb', '1wbU', '1Ull',
            ],
            'T_OCT_INT_LITERAL' => ['0', '00', '017', "0'7"],
            'T_HEX_INT_LITERAL' => ['0x1F', "0x1F'FF", '0XabCD', '0xFFFFFFFFFFFFFFFFFFFFFFFF', '0x1uwb'],
            'T_BIN_INT_LITERAL' => ['0b1010', "0B1'0"],
            'T_DEC_FLOAT_LITERAL' => [
                '1.5', '.5', '1.', '1e10', '1E+10', '1.5e-3', "1'000.000'1", '1.5f', '1.5F', '1.5l', '1.5L',
                '1.5df', '1.5DD', '1.5dl', '1.0f16', '1.0F32', '1.0f64', '1.0f128', '1.0f32x', '1.0f64x', '1.0f128x',
                '1.0d32', '1.0D64', '1.0d128', '1.0d64x', '1.0d128x', '1e999', "1e1'0", '08.5', '09e1',
            ],
            'T_HEX_FLOAT_LITERAL' => [
                '0x1.8p3', '0x.8p1', '0x1p-1074', '0X1P+4', '0x1.fffffffffffffp1023', '0x1p3f', "0x1p1'0", '0x1.p1',
            ],
        ];

        foreach ($constants as $token => $literals) {
            foreach ($literals as $literal) {
                yield 'constant ' . $literal => [$literal, [[$token, $literal]]];
            }
        }

        foreach ([
            "'a'", "'ab'", "'\\n'", "'\\''", "'\"'", "'\\0'", "'\\x41'", "'\\u00E9'",
            "u8'a'", "u'a'", "U'a'", "L'a'",
        ] as $literal) {
            yield 'character ' . $literal => [$literal, [['T_CHAR_LITERAL', $literal]]];
        }

        foreach ([
            '""', '"text"', '"a\\"b"', "\"'\"", '"\\\\"', '"\\x41\\101\\u00E9\\U0001F600"',
            'u8""', 'u""', 'U""', 'L""',
        ] as $literal) {
            yield 'string ' . $literal => [$literal, [['T_STRING_LITERAL', $literal]]];
        }

        yield 'adjacent strings' => ['"a" "b"', [['T_STRING_LITERAL', '"a"'], ['T_STRING_LITERAL', '"b"']]];
        yield 'prefix is not an identifier' => ['u8"x"', [['T_STRING_LITERAL', 'u8"x"']]];

        $punctuators = [
            'T_ELLIPSIS' => '...', 'T_ARROW' => '->', 'T_INCREMENT' => '++', 'T_DECREMENT' => '--',
            'T_SHL_ASSIGN' => '<<=', 'T_SHR_ASSIGN' => '>>=', 'T_LBRACKET' => '[', 'T_RBRACKET' => ']',
            'T_LBRACE' => '{', 'T_RBRACE' => '}', 'T_SHL' => '<<', 'T_SHR' => '>>', 'T_LESS_EQUAL' => '<=',
            'T_GREATER_EQUAL' => '>=', 'T_EQUAL' => '==', 'T_NOT_EQUAL' => '!=', 'T_LOGICAL_AND' => '&&',
            'T_LOGICAL_OR' => '||', 'T_MUL_ASSIGN' => '*=', 'T_DIV_ASSIGN' => '/=', 'T_MOD_ASSIGN' => '%=',
            'T_ADD_ASSIGN' => '+=', 'T_SUB_ASSIGN' => '-=', 'T_AND_ASSIGN' => '&=', 'T_XOR_ASSIGN' => '^=',
            'T_OR_ASSIGN' => '|=', 'T_DOUBLE_COLON' => '::', 'T_LPAREN' => '(', 'T_RPAREN' => ')',
            'T_DOT' => '.', 'T_AMPERSAND' => '&', 'T_ASTERISK' => '*', 'T_PLUS' => '+', 'T_MINUS' => '-',
            'T_TILDE' => '~', 'T_EXCLAMATION' => '!', 'T_SLASH' => '/', 'T_PERCENT' => '%', 'T_LESS' => '<',
            'T_GREATER' => '>', 'T_CARET' => '^', 'T_PIPE' => '|', 'T_QUESTION' => '?', 'T_COLON' => ':',
            'T_SEMICOLON' => ';', 'T_ASSIGN' => '=', 'T_COMMA' => ',',
        ];

        foreach ($punctuators as $name => $punctuator) {
            yield 'punctuator ' . $punctuator => [$punctuator, [[$name, $punctuator]]];
        }

        yield 'digraph <:' => ['<:', [['T_LBRACKET', '<:']]];
        yield 'digraph :>' => [':>', [['T_RBRACKET', ':>']]];
        yield 'digraph <%' => ['<%', [['T_LBRACE', '<%']]];
        yield 'digraph %>' => ['%>', [['T_RBRACE', '%>']]];
        yield 'longest punctuator wins' => ['a->b--c', [
            ['T_IDENTIFIER', 'a'], ['T_ARROW', '->'], ['T_IDENTIFIER', 'b'],
            ['T_DECREMENT', '--'], ['T_IDENTIFIER', 'c'],
        ]];
        yield 'dot before a digit is a floating constant' => ['a.b .5', [
            ['T_IDENTIFIER', 'a'], ['T_DOT', '.'], ['T_IDENTIFIER', 'b'], ['T_DEC_FLOAT_LITERAL', '.5'],
        ]];
        yield 'double colon of an attribute' => ['gnu::packed', [
            ['T_IDENTIFIER', 'gnu'], ['T_DOUBLE_COLON', '::'], ['T_IDENTIFIER', 'packed'],
        ]];

        yield 'whitespace' => [" \t\r\n\f\vx", [['T_IDENTIFIER', 'x']]];
        yield 'byte order mark at the beginning' => ["\xEF\xBB\xBFx", [['T_IDENTIFIER', 'x']]];
        yield 'line comment' => ["// int\nx", [['T_IDENTIFIER', 'x']]];
        yield 'block comment' => ["/* int\n */x", [['T_IDENTIFIER', 'x']]];
        yield 'comment in UTF-8' => ["/* \xD0\xBA\xD0\xBE\xD0\xB4 */x", [['T_IDENTIFIER', 'x']]];
        yield 'comment in invalid UTF-8' => ["/* \xFF\xC3 */x", [['T_IDENTIFIER', 'x']]];

        yield 'line marker' => ["# 1 \"x.h\" 3\nx", [['T_DIRECTIVE', '# 1 "x.h" 3'], ['T_IDENTIFIER', 'x']]];
        yield 'pragma' => ["#pragma pack(1)\nx", [['T_DIRECTIVE', '#pragma pack(1)'], ['T_IDENTIFIER', 'x']]];
        yield 'define' => ["#define FFI_SCOPE \"x\"\nx", [
            ['T_DIRECTIVE', '#define FFI_SCOPE "x"'],
            ['T_IDENTIFIER', 'x'],
        ]];
        yield 'indented directive' => ["x\n \t#pragma once\ny", [
            ['T_IDENTIFIER', 'x'], ['T_DIRECTIVE', " \t#pragma once"], ['T_IDENTIFIER', 'y'],
        ]];
        yield 'directive in the last line' => ["x\n#pragma once", [
            ['T_IDENTIFIER', 'x'], ['T_DIRECTIVE', '#pragma once'],
        ]];
        yield 'directive continued onto the next line' => ["#define A \\\n  1\nx", [
            ['T_DIRECTIVE', "#define A \\\n  1"],
            ['T_IDENTIFIER', 'x'],
        ]];
        yield 'directive after CRLF' => ["x\r\n#pragma once\r\ny", [
            ['T_IDENTIFIER', 'x'], ['T_DIRECTIVE', '#pragma once'], ['T_IDENTIFIER', 'y'],
        ]];
        yield 'directive continued onto the next line after CRLF' => ["#define A \\\r\n  1\r\nx", [
            ['T_DIRECTIVE', "#define A \\\r\n  1"],
            ['T_IDENTIFIER', 'x'],
        ]];
        yield 'directive spelled by a digraph' => ["%:pragma once\nx", [
            ['T_DIRECTIVE', '%:pragma once'], ['T_IDENTIFIER', 'x'],
        ]];
        yield 'directive indented by a form feed' => ["\f#pragma once\nx", [
            ['T_DIRECTIVE', "\f#pragma once"], ['T_IDENTIFIER', 'x'],
        ]];
        yield 'directive indented by a vertical tab' => ["\x0B#pragma once\nx", [
            ['T_DIRECTIVE', "\x0B#pragma once"], ['T_IDENTIFIER', 'x'],
        ]];
        yield 'indented directive after CRLF' => ["x\r\n  #pragma once\r\ny", [
            ['T_IDENTIFIER', 'x'], ['T_DIRECTIVE', '  #pragma once'], ['T_IDENTIFIER', 'y'],
        ]];
        yield 'indented directive after a trailing space' => ["x \n  #pragma once\ny", [
            ['T_IDENTIFIER', 'x'], ['T_DIRECTIVE', '  #pragma once'], ['T_IDENTIFIER', 'y'],
        ]];
        yield 'directive after the byte order mark' => ["\xEF\xBB\xBF#pragma once\nx", [
            ['T_DIRECTIVE', '#pragma once'], ['T_IDENTIFIER', 'x'],
        ]];
        yield 'byte of a legacy encoding is not whitespace' => ["\x85x", [['T_IDENTIFIER', "\x85x"]]];
        yield 'long block comment' => ['/*' . \str_repeat('x', 2_000_000) . '*/x', [['T_IDENTIFIER', 'x']]];
        yield 'block comment of stars' => ['/***/x', [['T_IDENTIFIER', 'x']]];
        yield 'hash not at the beginning of a line' => ['x # y', [
            ['T_IDENTIFIER', 'x'], ['Unknown', '#'], ['T_IDENTIFIER', 'y'],
        ]];

        yield 'string broken by a newline' => [
            "\"a\nb\"",
            [['Unknown', '"a'], ['T_IDENTIFIER', 'b'], ['Unknown', '"']],
        ];
        yield 'lone hash at the beginning of a line is a directive' => ['#', [['T_DIRECTIVE', '#']]];

        foreach ([
            '08', '09', '0b2', '0b', '0x', "1''0", "1'", "'1", '1lul', '1lL', '1Ll', '1uu', '1lll', '1w', '1b',
            '1.5ff', '1.5fl', '1.5dF', '0x1.8', '1e', '1e+', '1.5f8', "''", "'a", '"text',
            '"\\q"', "'\\q'", '"\\x"', '"\\u12"', '@', '$', '`',
        ] as $fragment) {
            yield 'not a lexeme ' . \addcslashes($fragment, "\n") => [$fragment, self::getUnknownTokens($fragment)];
        }
    }

    /**
     * @param list<array{string, string}> $expected
     */
    #[DataProvider('tokensDataProvider')]
    public function testSourceIsSplitIntoTokens(string $code, array $expected): void
    {
        self::assertSame($expected, self::lex($code));
    }

    /**
     * @return list<array{string, string}>
     */
    private static function lex(string $code): array
    {
        self::$lexer ??= (new RuntimeLexerTransformer())->transform(
            (new Compiler())
                ->load(FileSource::createFromPathname(self::GRAMMAR))
                ->build()
                ->lexer,
        );

        $result = [];

        foreach (self::$lexer->lex(new StringSource($code)) as $token) {
            if ($token->channel === Channel::EndOfInput) {
                break;
            }

            $result[] = [$token->name ?? $token->channel->name, $token->value];
        }

        return $result;
    }

    /**
     * A fragment no lexeme matches is read as tokens of the unknown channel
     * up to the next whitespace, so it reaches the parser as is.
     *
     * @return list<array{string, string}>
     */
    private static function getUnknownTokens(string $fragment): array
    {
        $result = [];

        foreach (\preg_split('/\s++/', $fragment, -1, \PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            $result[] = ['Unknown', $part];
        }

        return $result;
    }
}
