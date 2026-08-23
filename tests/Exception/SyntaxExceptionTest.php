<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Exception;

use FFI\Parser\Exception\ParseRuntimeException;
use FFI\Parser\Exception\SyntaxException;
use FFI\Parser\Parser;
use FFI\Parser\Tests\TestCase;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Lexer\Token\Token;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(SyntaxException::class)]
#[CoversClass(ParseRuntimeException::class)]
final class SyntaxExceptionTest extends TestCase
{
    public function testExceptionPointsToTheUnexpectedToken(): void
    {
        $source = new StringSource('int x @;');
        $token = new Token(1, 'T_UNKNOWN', Channel::Unknown, '@', 6);

        $e = SyntaxException::becauseUnexpectedToken($source, $token, 'Message');

        self::assertSame('Message', $e->getMessage());
        self::assertSame($source, $e->source);
        self::assertSame(6, $e->offset);
        self::assertSame(1, $e->length);
    }

    /**
     * Headers along with the way their failure is printed, up to the trace.
     *
     * @return iterable<non-empty-string, array{string, string}>
     */
    public static function renderedExceptionsDataProvider(): iterable
    {
        yield 'first line' => [
            "int x\nint y;\n",
            <<<'TEXT'
                error[SyntaxException]: Syntax error, unexpected "int" (T_INT) in the declaration, ";" expected
                1 | int x
                2 | int y;
                  | ^^^
                3 |
                TEXT,
        ];
        yield 'end of input without a line break' => [
            "int a;\nint b;\nint c",
            <<<'TEXT'
                error[SyntaxException]: Syntax error, unexpected end of input in the declaration, ";" expected
                1 | int a;
                2 | int b;
                3 | int c
                  |      ^
                TEXT,
        ];
        yield 'middle of the header' => [
            "int a;\nint b;\nint c = 08;\nint d;\nint e;\n",
            <<<'TEXT'
                error[SyntaxException]: Syntax error, unexpected "08" (T_PP_NUMBER) in the initializer
                1 | int a;
                2 | int b;
                3 | int c = 08;
                  |         ^^
                4 | int d;
                5 | int e;
                TEXT,
        ];
        yield 'CRLF line endings' => [
            "int a;\r\nint b = 08;\r\n",
            <<<'TEXT'
                error[SyntaxException]: Syntax error, unexpected "08" (T_PP_NUMBER) in the initializer
                1 | int a;
                2 | int b = 08;
                  |         ^^
                3 |
                TEXT,
        ];
        yield 'UTF-8 before the failure' => [
            "/* \xC3\x84\xC3\x96 */ int x = 08;",
            "error[SyntaxException]: Syntax error, unexpected \"08\" (T_PP_NUMBER) in the initializer\n"
                . "1 | /* \xC3\x84\xC3\x96 */ int x = 08;\n"
                . '  |                  ^^',
        ];
    }

    #[DataProvider('renderedExceptionsDataProvider')]
    public function testExceptionIsRenderedWithTheSnippet(string $code, string $expected): void
    {
        self::assertSame($expected, self::renderWithoutTrace(self::catchSyntaxException($code)));
    }

    public function testFileIsNamedInTheRenderedException(): void
    {
        $pathname = \tempnam(\sys_get_temp_dir(), 'ffi');

        self::assertIsString($pathname);

        try {
            \file_put_contents($pathname, "int a;\nint b");

            $rendered = (string) self::catchSyntaxException(new \SplFileInfo($pathname));
        } finally {
            \unlink($pathname);
        }

        self::assertStringContainsString("\n --> {$pathname}:2:6\n", $rendered);
    }

    public function testStringIsNotNamedInTheRenderedException(): void
    {
        self::assertStringNotContainsString(' --> ', (string) self::catchSyntaxException('int a'));
    }

    /**
     * A stream closed after the parsing is printed with the contents the
     * parser has read.
     */
    public function testExceptionOfClosedStreamIsRenderedWithTheSnippet(): void
    {
        $stream = \fopen('php://memory', 'w+b');

        self::assertIsResource($stream);

        \fwrite($stream, 'int a');
        \rewind($stream);

        try {
            $e = self::catchSyntaxException($stream);
        } finally {
            \fclose($stream);
        }

        self::assertSame(
            "error[SyntaxException]: Syntax error, unexpected end of input in the declaration, \";\" expected\n"
                . "1 | int a\n"
                . '  |      ^',
            self::renderWithoutTrace($e),
        );
    }

    public function testExceptionOfUnreadableSourceIsRenderedWithoutTheSnippet(): void
    {
        $source = FileSource::createFromPathname(__DIR__ . '/missing.h');
        $token = new Token(1, 'T_IDENTIFIER', Channel::Default, 'x', 0);

        $rendered = (string) SyntaxException::becauseUnexpectedToken($source, $token, 'Message');

        self::assertStringStartsWith(SyntaxException::class . ': Message in ', $rendered);
    }

    private static function catchSyntaxException(mixed $source): SyntaxException
    {
        try {
            (new Parser())->parse($source);
        } catch (SyntaxException $e) {
            return $e;
        }

        self::fail('SyntaxException has not been thrown');
    }

    private static function renderWithoutTrace(SyntaxException $e): string
    {
        $rendered = (string) $e;
        $trace = \strpos($rendered, "\n#0 ");

        self::assertIsInt($trace);

        return \substr($rendered, 0, $trace);
    }
}
