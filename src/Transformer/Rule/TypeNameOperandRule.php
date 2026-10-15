<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule;

use FFI\Parser\Node\Expression\BinaryExpression\CommaExpression;
use FFI\Parser\Node\Expression\BinaryExpression\MulExpression;
use FFI\Parser\Node\Expression\CallExpression;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Expression\ParenExpression;
use FFI\Parser\Node\Expression\SubscriptExpression;
use FFI\Parser\Node\Expression\UnaryExpression\DereferenceExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\ArrayType\SizedArrayType;
use FFI\Parser\Node\Type\FunctionType;
use FFI\Parser\Node\Type\FunctionType\Parameter;
use FFI\Parser\Node\Type\FunctionType\ParameterList;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\PointerType;
use FFI\Parser\Node\Type\TypeInterface;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformerRuleInterface;

/**
 * Base of the rules that turn an operand parsed as an expression into a
 * type name, when the expression starts with a typedef name.
 *
 * The expression is converted only when the type specifier of the type
 * name and of each of its parameters is a typedef name. The offsets of `*`
 * between a typedef name and the parenthesis, of `[` and of the parenthesis
 * of a function with parameters are not kept in the tree, the type they
 * start is given the offset of the node after them.
 *
 * @template TNode of NodeInterface
 *
 * @template-implements TransformerRuleInterface<TNode>
 */
abstract class TypeNameOperandRule implements TransformerRuleInterface
{
    /**
     * Converts the expression a type name is parsed into: the typedef name
     * with the subscripts and the calls applied to it (`T[3]`, `T(U)`), or
     * multiplied by the rest of the declarator (`T *(U)`).
     *
     * @return TypeInterface|null the type, {@see null} in case of the
     *         expression is not a type name
     */
    protected function findTypeName(ExpressionInterface $expression, TransformationContext $context): ?TypeInterface
    {
        if ($expression instanceof MulExpression) {
            $type = $this->findTypedefName($expression->left, $context);

            if ($type === null) {
                return null;
            }

            $pointer = new PointerType($type, null, $expression->right->offset);

            return $this->findAbstractDeclarator($expression->right, $pointer, $context);
        }

        $operand = $this->getSuffixedOperand($expression);
        $type = $this->findTypedefName($operand, $context);

        if ($type === null) {
            return null;
        }

        return $this->findSuffixedType($expression, $operand, $type, $context);
    }

    private function findTypedefName(ExpressionInterface $expression, TransformationContext $context): ?NamedType
    {
        if (!$expression instanceof IdentifierExpression || !$context->isTypedefName($expression->name->value)) {
            return null;
        }

        return new NamedType($expression->name, $expression->offset);
    }

    /**
     * Converts the rest of an abstract declarator after `*`: the pointers
     * and the parenthesized parameters with the subscripts and the calls
     * applied to them.
     *
     * @return TypeInterface|null the type, {@see null} in case of the
     *         expression is not an abstract declarator
     */
    private function findAbstractDeclarator(
        ExpressionInterface $expression,
        TypeInterface $type,
        TransformationContext $context,
    ): ?TypeInterface {
        if ($expression instanceof DereferenceExpression) {
            $pointer = new PointerType($type, null, $expression->offset);

            return $this->findAbstractDeclarator($expression->operand, $pointer, $context);
        }

        $operand = $this->getSuffixedOperand($expression);

        if (!$operand instanceof ParenExpression) {
            return null;
        }

        $parameters = $this->findParameters($this->getCommaOperands($operand->expression), $context);

        if ($parameters === null) {
            return null;
        }

        $type = $this->findSuffixedType($expression, $operand, $type, $context);

        if ($type === null) {
            return null;
        }

        return new FunctionType(
            $type,
            new ParameterList($parameters, $operand->expression->offset),
            offset: $operand->offset,
        );
    }

    /**
     * Returns the operand the subscripts and the calls the expression
     * starts with are applied to.
     */
    private function getSuffixedOperand(ExpressionInterface $expression): ExpressionInterface
    {
        while ($expression instanceof SubscriptExpression || $expression instanceof CallExpression) {
            $expression = $expression instanceof SubscriptExpression ? $expression->array : $expression->function;
        }

        return $expression;
    }

    /**
     * Applies the subscripts and the calls between the expression and its
     * suffixed operand to the type, as the array and the function
     * declarators.
     *
     * @return TypeInterface|null the type, {@see null} in case of an
     *         argument of a call is not a type name
     */
    private function findSuffixedType(
        ExpressionInterface $expression,
        ExpressionInterface $operand,
        TypeInterface $type,
        TransformationContext $context,
    ): ?TypeInterface {
        while ($expression !== $operand) {
            if ($expression instanceof SubscriptExpression) {
                $type = new SizedArrayType($type, $expression->index, offset: $expression->index->offset);
                $expression = $expression->array;

                continue;
            }

            \assert($expression instanceof CallExpression);

            $parameters = $this->findParameters($expression->arguments->items, $context);

            if ($parameters === null) {
                return null;
            }

            $type = new FunctionType(
                $type,
                new ParameterList($parameters, $expression->arguments->offset),
                offset: $expression->arguments->offset,
            );
            $expression = $expression->function;
        }

        return $type;
    }

    /**
     * @param list<ExpressionInterface> $arguments
     * @return list<Parameter>|null the parameters without names,
     *         {@see null} in case of an argument is not a type name
     */
    private function findParameters(array $arguments, TransformationContext $context): ?array
    {
        $parameters = [];

        foreach ($arguments as $argument) {
            $type = $this->findTypeName($argument, $context);

            if ($type === null) {
                return null;
            }

            $parameters[] = new Parameter($type, offset: $argument->offset);
        }

        return $parameters;
    }

    /**
     * @return non-empty-list<ExpressionInterface> the operands of the comma
     *         operators of the expression, the expression itself in case of
     *         it is not a comma operator
     */
    private function getCommaOperands(ExpressionInterface $expression): array
    {
        if ($expression instanceof CommaExpression) {
            return [...$this->getCommaOperands($expression->left), ...$this->getCommaOperands($expression->right)];
        }

        return [$expression];
    }
}
