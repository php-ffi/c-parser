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

#[CoversNothing]
final class LexemesTest extends TestCase
{
    private const GRAMMAR = __DIR__ . '/../../resources/grammar.pp3';

    private const IGNORED_TOKENS = [
        'T_BYTE_ORDER_MARK',
        'T_COMMENT_LINE',
        'T_COMMENT_BLOCK',
        'T_NEWLINE',
        'T_WHITESPACE',
    ];

    private static LexerInterface $lexer;

    /**
     * @return iterable<non-empty-string, array{string, list<array{string, string}>}>
     */
    public static function tokensDataProvider(): iterable
    {
        $keywords = [
            'T_ALIGNAS' => ['alignas', '_Alignas'],
            'T_ALIGNOF' => ['alignof', '_Alignof', '__alignof', '__alignof__'],
            'T_AUTO' => ['auto'],
            'T_BOOL' => ['bool', '_Bool'],
            'T_CHAR' => ['char'],
            'T_CONST' => ['const', '__const', '__const__'],
            'T_CONSTEXPR' => ['constexpr'],
            'T_DEFAULT' => ['default'],
            'T_DOUBLE' => ['double'],
            'T_ENUM' => ['enum'],
            'T_EXTERN' => ['extern'],
            'T_FALSE' => ['false'],
            'T_FLOAT' => ['float'],
            'T_INLINE' => ['inline', '__inline', '__inline__'],
            'T_INT' => ['int'],
            'T_LONG' => ['long'],
            'T_NULLPTR' => ['nullptr'],
            'T_REGISTER' => ['register'],
            'T_RESTRICT' => ['restrict', '__restrict', '__restrict__'],
            'T_SHORT' => ['short'],
            'T_SIGNED' => ['signed', '__signed', '__signed__'],
            'T_SIZEOF' => ['sizeof'],
            'T_STATIC_ASSERT' => ['static_assert', '_Static_assert'],
            'T_STATIC' => ['static'],
            'T_STRUCT' => ['struct'],
            'T_THREAD_LOCAL' => ['thread_local', '_Thread_local', '__thread'],
            'T_TRUE' => ['true'],
            'T_TYPEDEF' => ['typedef'],
            'T_TYPEOF_UNQUAL' => ['typeof_unqual', '__typeof_unqual', '__typeof_unqual__'],
            'T_TYPEOF' => ['typeof', '__typeof', '__typeof__'],
            'T_UNION' => ['union'],
            'T_UNSIGNED' => ['unsigned'],
            'T_VOID' => ['void'],
            'T_VOLATILE' => ['volatile', '__volatile', '__volatile__'],
            'T_ATOMIC' => ['_Atomic'],
            'T_BITINT' => ['_BitInt'],
            'T_COMPLEX' => ['_Complex', '__complex', '__complex__'],
            'T_DECIMAL_NX' => ['_Decimal64x', '_Decimal128x'],
            'T_DECIMAL_N' => ['_Decimal32', '_Decimal64', '_Decimal96', '_Decimal128', '_Decimal160'],
            'T_FLOAT_NX' => ['_Float32x', '_Float64x', '_Float128x'],
            'T_FLOAT_N' => ['_Float16', '_Float32', '_Float64', '_Float128', '_Float256'],
            'T_GENERIC' => ['_Generic'],
            'T_IMAGINARY' => ['_Imaginary'],
            'T_NORETURN' => ['_Noreturn'],
            'T_RESERVED_KEYWORD' => [
                'break', 'case', 'continue', 'do', 'else', 'for', 'goto', 'if', 'return', 'switch', 'while',
            ],
            'T_GNU_ATTRIBUTE' => ['__attribute__', '__attribute'],
            'T_ASM' => ['__asm__', '__asm'],
            'T_EXTENSION' => ['__extension__'],
            'T_REAL' => ['__real__', '__real'],
            'T_IMAG' => ['__imag__', '__imag'],
            'T_DECLSPEC' => ['__declspec'],
            'T_FORCE_INLINE' => ['__forceinline'],
            'T_POINTER_MODIFIER' => ['__ptr32', '__ptr64', '__sptr', '__uptr', '__unaligned', '__w64'],
            'T_BASED' => ['__based'],
            'T_NULLABILITY' => ['_Nonnull', '_Nullable_result', '_Nullable', '_Null_unspecified'],
            'T_PTRAUTH' => ['__ptrauth'],
            'T_CALLING_CONVENTION' => [
                '__cdecl', '__stdcall', '__fastcall', '__vectorcall', '__thiscall', '__clrcall', '__preserve_none',
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
        yield 'integer type of a compiler' => ['__int64', [['T_IDENTIFIER', '__int64']]];
        yield 'typedef name after an integer type of a compiler' => ['__int128_t', [['T_IDENTIFIER', '__int128_t']]];
        yield 'atomic type specifier' => ["_Atomic \n(", [['T_ATOMIC_SPECIFIER', '_Atomic'], ['T_LPAREN', '(']]];
        yield 'atomic type specifier with a comment' => ['_Atomic /* a */ // b' . "\n(", [
            ['T_ATOMIC_SPECIFIER', '_Atomic'], ['T_LPAREN', '('],
        ]];
        yield 'atomic qualifier' => ['_Atomic int', [['T_ATOMIC', '_Atomic'], ['T_INT', 'int']]];
        yield 'identifier in UTF-8' => ["caf\xC3\xA9", [['T_IDENTIFIER', "caf\xC3\xA9"]]];
        yield 'identifier of universal character names' => ['\\u00E9t\\U0001F600', [
            ['T_IDENTIFIER', '\\u00E9t\\U0001F600'],
        ]];
        yield 'floating type without a width' => ['_Float _Decimal', [
            ['T_IDENTIFIER', '_Float'], ['T_IDENTIFIER', '_Decimal'],
        ]];
        yield 'floating type with a leading zero' => ['_Float032 _Decimal064x', [
            ['T_IDENTIFIER', '_Float032'], ['T_IDENTIFIER', '_Decimal064x'],
        ]];
        yield 'typedef name after a floating type' => ['_Float16_t _Float32xy', [
            ['T_IDENTIFIER', '_Float16_t'], ['T_IDENTIFIER', '_Float32xy'],
        ]];

        foreach ([
            'asm', '__asm_', '__asm___', '__attribute_', '__attribute___', '_declspec', '__declspec__', '__extension', '_const',
            '__const_', '__inline___', '_restrict', '__typeof_', '__alignof_', '__thread__', '_thread', '__cdecl_',
            '_cdecl', '_stdcall', 'cdecl', '__pascal', '_ptr64', '__ptr64_', '_forceinline', '__forceinline__',
            '_Nonnull_', '__based_', '__ptrauth__', '__real___', '__imag_', '__builtin_offsetof',
            '__pragma', '__int64',
        ] as $name) {
            yield 'identifier ' . $name => [$name, [['T_IDENTIFIER', $name]]];
        }

        $constants = [
            'T_DEC_INT_LITERAL' => [
                '42', "1'000'000", '1u', '1U', '1l', '1L', '1ll', '1LL', '1ul', '1lu', '1ULL', '1llu', '1wb', '1WB',
                '1uwb', '1wbU', '1Ull', '1i', '1J', '1ui', '1uli', '1iul', '1i8', '1I16', '1i32', '1i64', '1ui64', '1UI64',
            ],
            'T_OCT_INT_LITERAL' => ['0', '00', '017', "0'7"],
            'T_HEX_INT_LITERAL' => ['0x1', '0x1F', "0x1F'FF", '0XabCD', '0xFFFFFFFFFFFFFFFFFFFFFFFF', '0x1uwb'],
            'T_BIN_INT_LITERAL' => ['0b1010', "0B1'0"],
            'T_DEC_FLOAT_LITERAL' => [
                '1.5', '.5', '1.', '1e10', '1E+10', '1.5e-3', "1'000.000'1", '1.5f', '1.5F', '1.5l', '1.5L',
                '1.5df', '1.5DD', '1.5dl', '1.0f16', '1.0F32', '1.0f64', '1.0f128', '1.0f32x', '1.0f64x', '1.0f128x', '1.0f256', '1.0d96', '1.0D160x',
                '1.0d32', '1.0D64', '1.0d128', '1.0d64x', '1.0d128x', '1e999', "1e1'0", '08.5', '09e1',
                '0.5', '0e1', '2.0i', '2.0fi', '2.0if', '1e3J', '1.0q', '1.0Q', '1.0w', '1.0W', '1.0qi',
            ],
            'T_HEX_FLOAT_LITERAL' => [
                '0x1.8p3', '0x.8p1', '0x1p-1074', '0X1P+4', '0x1.fffffffffffffp1023', '0x1p3f', "0x1p1'0", '0x1.p1',
                '0x1p3q',
            ],
        ];

        foreach ($constants as $token => $literals) {
            foreach ($literals as $literal) {
                yield 'constant ' . $literal => [$literal, [[$token, $literal]]];
            }
        }

        foreach ([
            "'a'", "'ab'", "'\\n'", "'\\''", "'\"'", "'\\0'", "'\\x41'", "'\\u00E9'", "'\\e'", "'\\E'",
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
        yield 'digraph %: not at the beginning of a line' => ['x %:', [['T_IDENTIFIER', 'x'], ['T_HASH', '%:']]];
        yield 'digraph %:%: not at the beginning of a line' => ['x %:%:', [
            ['T_IDENTIFIER', 'x'], ['T_HASH_HASH', '%:%:'],
        ]];
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
        yield 'pragma operator' => ['x _Pragma("once") y', [
            ['T_IDENTIFIER', 'x'], ['T_PRAGMA_OPERATOR', '_Pragma("once")'], ['T_IDENTIFIER', 'y'],
        ]];
        yield 'parenthesis in a string of a pragma operator' => ['_Pragma("message(\")\")")', [
            ['T_PRAGMA_OPERATOR', '_Pragma("message(\")\")")'],
        ]];
        yield 'pragma operator of MSVC' => ['x __pragma(pack(push, 8)) y', [
            ['T_IDENTIFIER', 'x'], ['T_PRAGMA_OPERATOR', '__pragma(pack(push, 8))'], ['T_IDENTIFIER', 'y'],
        ]];
        yield 'line splice' => ["x \\\ny \\\r\nz", [
            ['T_IDENTIFIER', 'x'], ['T_LINE_SPLICE', "\\\n"], ['T_IDENTIFIER', 'y'], ['T_LINE_SPLICE', "\\\r\n"],
            ['T_IDENTIFIER', 'z'],
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
            ['T_IDENTIFIER', 'x'], ['T_HASH', '#'], ['T_IDENTIFIER', 'y'],
        ]];
        yield 'double hash not at the beginning of a line' => ['x ## y', [
            ['T_IDENTIFIER', 'x'], ['T_HASH_HASH', '##'], ['T_IDENTIFIER', 'y'],
        ]];

        yield 'string broken by a newline' => [
            "\"a\nb\"",
            [['Unknown', '"a'], ['T_IDENTIFIER', 'b'], ['Unknown', '"']],
        ];
        yield 'lone hash at the beginning of a line is a directive' => ['#', [['T_DIRECTIVE', '#']]];

        foreach ([
            '08', '09', '0b2', '0b', '0x', '1lul', '1lL', '1Ll', '1uu', '1lll', '1w', '1b', '1.5ff', '1.5fl', '1.5dF',
            '0x1.8', '1e', '1e+', '1.5f08', '1.5d0', '1.2.3', '1..2', '0x1.', '0x1p3.5', '1u8', '0xE+1', '10.13.4', '.5.5',
            "1'a", '1q', '1i128', '1i64i', '1i64l', '1.0i64', '1i4', '1iu64', '1ii', '2.0ij', '1.0qw',
        ] as $number) {
            yield 'number of the preprocessor ' . $number => [$number, [['T_PP_NUMBER', $number]]];
        }

        yield 'doubled digit separator' => ["1''0", [['T_PP_NUMBER', '1'], ['Unknown', "''0"]]];
        yield 'trailing digit separator' => ["1'", [['T_PP_NUMBER', '1'], ['Unknown', "'"]]];

        foreach (["'1", "''", "'a", "'\\q'", "'ab\\"] as $fragment) {
            yield 'invalid character ' . $fragment => [$fragment, [['Unknown', $fragment]]];
        }

        yield 'invalid character u8\'x' => ["u8'x", [['T_IDENTIFIER', 'u8'], ['Unknown', "'x"]]];

        foreach (['"text', '"\\q"', '"\\x"', '"\\u12"'] as $fragment) {
            yield 'invalid string ' . $fragment => [$fragment, [['Unknown', $fragment]]];
        }

        yield 'invalid string L"x' => ['L"x', [['T_IDENTIFIER', 'L'], ['Unknown', '"x']]];

        foreach (['@', '$', '`'] as $fragment) {
            yield 'not a lexeme ' . $fragment => [$fragment, [['Unknown', $fragment]]];
        }

        yield 'start of an attribute' => ['[[', [['T_ATTRIBUTE_START', '[[']]];
        yield 'start of an attribute in digraphs' => ['<:<:', [['T_ATTRIBUTE_START', '<:<:']]];
        yield 'start of an attribute with whitespace' => ["[ \n[", [['T_ATTRIBUTE_START', "[ \n["]]];
        yield 'start of an attribute with comments' => ["[ /* a */ // b\n[", [
            ['T_ATTRIBUTE_START', "[ /* a */ // b\n["],
        ]];
        yield 'end of an attribute is two brackets' => [']]', [['T_RBRACKET', ']'], ['T_RBRACKET', ']']]];
        yield 'dot before a digit is not a punctuator' => ['.5 .', [['T_DEC_FLOAT_LITERAL', '.5'], ['T_DOT', '.']]];
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
        self::$lexer ??= (new RuntimeLexerTransformer(skip: []))->transform(
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

            if (\in_array($token->name, self::IGNORED_TOKENS, true)) {
                continue;
            }

            $result[] = [$token->name ?? $token->channel->name, $token->value];
        }

        return $result;
    }
}
