<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\BuiltinType\IntType;
use FFI\Parser\Validator;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Validator::class)]
#[CoversClass(ValidationContext::class)]
#[CoversClass(Violation::class)]
final class ValidatorTest extends TestCase
{
    public function testEveryNodeIsVisitedOnce(): void
    {
        $validator = self::createRecordingValidator();

        (new Validator([$validator]))->validate(self::parse('struct S { int a : 3; } x, *y = 0;'));

        self::assertSame(
            [
                'VariableDeclaration@0',
                '  Identifier@24',
                '  StructType@0',
                '    Identifier@7',
                '    MemberDeclarationList@11',
                '      MemberDeclaration@11',
                '        IntType@11',
                '        Identifier@15',
                '        DecIntLiteral@19',
                'VariableDeclaration@0',
                '  Identifier@28',
                '  OctIntLiteral@32',
                '  PointerType@27',
            ],
            $validator->visits,
        );
    }

    public function testContextHoldsTheParentsAndTheDeclarations(): void
    {
        $declarations = self::parse('int x; struct S { int a; } y;');
        $validator = self::createRecordingValidator();

        (new Validator([$validator]))->validate($declarations);

        $parents = [];

        foreach ($validator->contexts as $index => $context) {
            self::assertSame($declarations, $context->declarations);

            if ($validator->nodes[$index] instanceof IntType && $validator->nodes[$index]->offset === 18) {
                $parents = \array_map(
                    static fn(NodeInterface $parent): string => (new \ReflectionClass($parent))->getShortName(),
                    $context->parents,
                );
            }
        }

        self::assertSame(['VariableDeclaration', 'StructType', 'MemberDeclarationList', 'MemberDeclaration'], $parents);
    }

    public function testViolationsOfEveryValidatorAreSortedByOffset(): void
    {
        $violations = (new Validator([
            self::createRejectingValidator(Identifier::class),
            self::createRejectingValidator(IntType::class),
        ]))->validate(self::parse('int a; int b = c;'));

        self::assertSame(
            [
                [0, 'IntType'],
                [4, 'Identifier'],
                [7, 'IntType'],
                [11, 'Identifier'],
                [15, 'Identifier'],
            ],
            \array_map(static fn(Violation $v): array => [$v->offset, $v->message], $violations),
        );
    }

    public function testValidatorsAreTakenFromAnyIterable(): void
    {
        $validators = (static function (): \Generator {
            yield 'expressions' => self::createRejectingValidator(IdentifierExpression::class);
        })();

        self::assertCount(1, (new Validator($validators))->validate(self::parse('int a = b;')));
    }

    public function testDeclarationsWithoutValidatorsHaveNoViolations(): void
    {
        self::assertSame([], (new Validator())->validate(self::parse('int a;')));
    }

    /**
     * @return ValidatorRuleInterface&object{
     *     visits: list<non-empty-string>,
     *     nodes: list<NodeInterface>,
     *     contexts: list<ValidationContext>,
     * }
     */
    private static function createRecordingValidator(): ValidatorRuleInterface
    {
        return new class implements ValidatorRuleInterface {
            /**
             * @var list<non-empty-string>
             */
            public array $visits = [];

            /**
             * @var list<NodeInterface>
             */
            public array $nodes = [];

            /**
             * @var list<ValidationContext>
             */
            public array $contexts = [];

            public function validate(NodeInterface $node, ValidationContext $context): iterable
            {
                $this->visits[] = \str_repeat('  ', \count($context->parents))
                    . (new \ReflectionClass($node))->getShortName() . '@' . $node->offset;
                $this->nodes[] = $node;
                $this->contexts[] = $context;

                return [];
            }
        };
    }

    /**
     * @param class-string<NodeInterface> $class
     */
    private static function createRejectingValidator(string $class): ValidatorRuleInterface
    {
        return new class ($class) implements ValidatorRuleInterface {
            /**
             * @param class-string<NodeInterface> $class
             */
            public function __construct(
                private readonly string $class,
            ) {}

            public function validate(NodeInterface $node, ValidationContext $context): iterable
            {
                if ($node instanceof $this->class) {
                    yield new Violation((new \ReflectionClass($node))->getShortName(), $node->offset);
                }
            }
        };
    }

}
