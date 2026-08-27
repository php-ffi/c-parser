<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Exception;

use FFI\Parser\Exception\ValidationException;
use FFI\Parser\Tests\TestCase;
use FFI\Parser\Validator\Violation;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ValidationException::class)]
final class ValidationExceptionTest extends TestCase
{
    public function testExceptionPointsToTheFirstViolation(): void
    {
        $source = new StringSource("int a;\nlong char b;");
        $violations = [new Violation('Invalid type', 7, 9)];

        $e = ValidationException::becauseValidationFailed($source, $violations);

        self::assertSame('Invalid type', $e->getMessage());
        self::assertSame($source, $e->source);
        self::assertSame([7, 9], [$e->offset, $e->length]);
        self::assertSame($violations, $e->violations);
    }

    public function testMessageCountsTheOtherViolations(): void
    {
        $violations = [new Violation('First', 0), new Violation('Second', 4), new Violation('Third', 8)];

        $e = ValidationException::becauseValidationFailed(new StringSource('int a, b, c;'), $violations);

        self::assertSame('First (and 2 more)', $e->getMessage());
        self::assertSame(0, $e->offset);
        self::assertNull($e->length);
        self::assertSame($violations, $e->violations);
    }

    public function testExceptionIsRenderedWithTheSnippetOfTheFirstViolation(): void
    {
        $e = ValidationException::becauseValidationFailed(
            new StringSource("int a;\nlong char b;"),
            [new Violation('Invalid type', 7, 9)],
        );
        $rendered = (string) $e;

        self::assertStringStartsWith(
            "error[ValidationException]: Invalid type\n"
                . "1 | int a;\n"
                . "2 | long char b;\n"
                . '  | ^^^^^^^^^',
            $rendered,
        );
    }
}
