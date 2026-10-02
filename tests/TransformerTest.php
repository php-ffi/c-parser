<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\RecordType\StructType;
use FFI\Parser\Transformer;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformerRuleInterface;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Transformer::class)]
#[CoversClass(TransformationContext::class)]
final class TransformerTest extends TestCase
{
    public function testNodeIsGivenAfterItsChildren(): void
    {
        $transformer = self::createRecordingTransformer();

        (new Transformer([$transformer]))->transform(self::parse('int a = b;'));

        self::assertSame(
            [
                '  SpecifierList@0',
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

        (new Transformer([$transformer]))->transform(self::parse('struct S {} x, *y;'));

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
        ]))->transform(self::parse('struct S {} x, *y;'));

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
    private static function createTransformer(\Closure $transform): TransformerRuleInterface
    {
        return new class ($transform) implements TransformerRuleInterface {
            public function __construct(
                /**
                 * @var \Closure(NodeInterface, TransformationContext): (NodeInterface|null)
                 */
                private readonly \Closure $onTransform,
            ) {}

            public function transform(NodeInterface $node, TransformationContext $context): ?NodeInterface
            {
                return ($this->onTransform)($node, $context);
            }
        };
    }
}
