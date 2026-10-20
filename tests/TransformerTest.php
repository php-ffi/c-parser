<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\BuiltinType\IntType;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\RecordType\StructType;
use FFI\Parser\Parser;
use FFI\Parser\Transformer;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformationContext\Scope;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Transformer::class)]
#[CoversClass(TransformationContext::class)]
#[CoversClass(Scope::class)]
final class TransformerTest extends TestCase
{
    public function testNodeIsGivenAfterItsChildren(): void
    {
        $transformer = self::createRecordingTransformer();

        (new Transformer([$transformer]))->transform(self::parse('int a = b;'));

        self::assertSame(
            [
                '  Identifier@4',
                '    Identifier@8',
                '  IdentifierExpression@8',
                '  IntType@0',
                'VariableDeclaration@0',
            ],
            $transformer->visits,
        );
    }

    public function testSharedNodeIsGivenOnce(): void
    {
        $transformer = self::createRecordingTransformer();

        (new Transformer([$transformer]))->transform(self::parse('struct S { int a; } x, *y;'));

        self::assertSame(['  StructType@0'], \array_values(\array_filter(
            $transformer->visits,
            static fn(string $visit): bool => \str_contains($visit, 'StructType'),
        )));
    }

    public function testReplacementTakesThePlaceOfSharedNodeInEveryParent(): void
    {
        $declarations = (new Transformer([
            self::createTransformer(
                static fn(NodeInterface $node): ?NodeInterface => $node instanceof StructType
                    ? new NamedType(new Identifier('S', 7), 0)
                    : null,
            ),
        ]))->transform(self::parse('struct S { int a; } x, *y;'));

        self::assertSame(
            ['(variable S x)', '(variable (ptr S) y)'],
            \array_map(NodePrinter::print(...), $declarations),
        );
    }

    public function testNodeCanBeWrappedByItsReplacement(): void
    {
        $declarations = (new Transformer([
            self::createTransformer(
                static fn(NodeInterface $node): ?NodeInterface => $node instanceof IdentifierExpression
                    ? new ParenExpression($node, 8)
                    : null,
            ),
        ]))->transform(self::parse('int a = b;'));

        self::assertSame('(variable int a = (paren b))', NodePrinter::print($declarations[0]));
    }

    public function testEachTransformerIsGivenTheResultOfThePreviousOne(): void
    {
        $transformer = self::createRecordingTransformer();

        (new Transformer([
            self::createTransformer(
                static fn(NodeInterface $node): ?NodeInterface => $node instanceof IdentifierExpression
                    ? new ParenExpression($node, 8)
                    : null,
            ),
            $transformer,
        ]))->transform(self::parse('int a = b;'));

        self::assertContains('  ParenExpression@8', $transformer->visits);
        self::assertNotContains('  IdentifierExpression@8', $transformer->visits);
    }

    public function testTransformerIsGivenTheNodesOfTheClassesItLists(): void
    {
        $visits = [];

        (new Transformer([
            self::createTransformer(
                static function (NodeInterface $node) use (&$visits): ?NodeInterface {
                    $visits[] = (new \ReflectionClass($node))->getShortName();

                    return null;
                },
                [IntType::class, ExpressionInterface::class],
            ),
        ]))->transform(self::parse('int a = b + 1; char c;'));

        self::assertSame(['IdentifierExpression', 'DecIntLiteral', 'AddExpression', 'IntType'], $visits);
    }

    public function testReplacementOfAnotherClassIsGivenToTheTransformersOfItsClassListedAfter(): void
    {
        $visits = [];
        $record = static function (string $name) use (&$visits): \Closure {
            return static function () use ($name, &$visits): ?NodeInterface {
                $visits[] = $name;

                return null;
            };
        };

        (new Transformer([
            self::createTransformer($record('paren before'), [ParenExpression::class]),
            self::createTransformer(
                static fn(NodeInterface $node): ?NodeInterface => new ParenExpression($node, $node->offset),
                [IdentifierExpression::class],
            ),
            self::createTransformer($record('identifier after'), [IdentifierExpression::class]),
            self::createTransformer($record('paren after'), [ParenExpression::class]),
        ]))->transform(self::parse('int a = b;'));

        self::assertSame(['paren after'], $visits);
    }

    public function testContextHoldsTheDeclarations(): void
    {
        $declarations = self::parse('int x; long y;');
        $transformer = self::createRecordingTransformer();

        (new Transformer([$transformer]))->transform($declarations);

        self::assertNotSame([], $transformer->contexts);

        foreach ($transformer->contexts as $context) {
            self::assertSame($declarations, $context->declarations);
        }
    }

    public function testDeclarationsAreReturnedAsIsWithoutTransformers(): void
    {
        $declarations = self::parse('int x; long y;');

        self::assertSame($declarations, (new Transformer())->transform($declarations));
    }

    public function testNamesAreDeclaredInTheOrderOfTheDeclarations(): void
    {
        $transformer = self::createNameRecordingTransformer();

        (new Transformer([$transformer]))->transform((new Parser(transformerRules: []))->parse(
            'int a = T; typedef int T; int b = T; enum { E }; int c = E + W;',
        ));

        self::assertSame(['T unknown', 'T typedef', 'E non-type', 'W unknown'], $transformer->names);
    }

    public function testParameterHidesTypedefNameInItsPrototypeOnly(): void
    {
        $transformer = self::createNameRecordingTransformer();

        (new Transformer([$transformer]))->transform((new Parser(transformerRules: []))->parse(
            'typedef int T; void f(int T, int a[sizeof(T)]); int b = T;',
        ));

        self::assertSame(['T non-type', 'T typedef'], $transformer->names);
    }

    public function testGivenTypedefNamesAreDeclaredBeforeTheDeclarations(): void
    {
        $transformer = self::createNameRecordingTransformer();

        (new Transformer([$transformer], ['T']))->transform((new Parser(transformerRules: []))->parse(
            'int a = T; void f(int T, int b[sizeof(T)]); int T; int c = T;',
        ));

        self::assertSame(['T typedef', 'T non-type', 'T non-type'], $transformer->names);
    }

    public function testTypedefNameIsNotDeclaredInsideItsOwnDeclaration(): void
    {
        $transformer = self::createNameRecordingTransformer();

        (new Transformer([$transformer]))->transform((new Parser(transformerRules: []))->parse(
            'typedef int T[sizeof(T)];',
        ));

        self::assertSame(['T unknown'], $transformer->names);
    }

    /**
     * @return TransformerRuleInterface&object{names: list<string>}
     */
    private static function createNameRecordingTransformer(): TransformerRuleInterface
    {
        return new class implements TransformerRuleInterface {
            /**
             * @var list<string>
             */
            public array $names = [];

            public function getNodeClasses(): iterable
            {
                return [NodeInterface::class];
            }

            public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
            {
                if ($node instanceof IdentifierExpression || $node instanceof NamedType) {
                    $this->names[] = $node->name->value . ' ' . match (true) {
                        $context->isTypedefName($node->name->value) => 'typedef',
                        $context->isNonTypeName($node->name->value) => 'non-type',
                        default => 'unknown',
                    };
                }

                return null;
            }
        };
    }

    /**
     * @return TransformerRuleInterface&object{visits: list<string>, contexts: list<TransformationContext>}
     */
    private static function createRecordingTransformer(): TransformerRuleInterface
    {
        return new class implements TransformerRuleInterface {
            /**
             * @var list<string>
             */
            public array $visits = [];

            /**
             * @var list<TransformationContext>
             */
            public array $contexts = [];

            public function getNodeClasses(): iterable
            {
                return [NodeInterface::class];
            }

            public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
            {
                $this->visits[] = \str_repeat('  ', \count($context->parents))
                    . (new \ReflectionClass($node))->getShortName() . '@' . $node->offset;
                $this->contexts[] = $context;

                return null;
            }
        };
    }

    /**
     * @param \Closure(NodeInterface, TransformationContext): (NodeInterface|null) $transform
     */
    /**
     * @param list<class-string<NodeInterface>> $classes
     */
    private static function createTransformer(
        \Closure $transform,
        array $classes = [NodeInterface::class],
    ): TransformerRuleInterface {
        return new class ($transform, $classes) implements TransformerRuleInterface {
            public function __construct(
                /**
                 * @var \Closure(NodeInterface, TransformationContext): (NodeInterface|null)
                 */
                private readonly \Closure $onTransform,
                /**
                 * @var list<class-string<NodeInterface>>
                 */
                private readonly array $classes,
            ) {}

            public function getNodeClasses(): iterable
            {
                return $this->classes;
            }

            public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
            {
                return ($this->onTransform)($node, $context);
            }
        };
    }
}
