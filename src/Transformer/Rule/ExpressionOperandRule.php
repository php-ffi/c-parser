<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule;

use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\TypeInterface;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformerRuleInterface;

/**
 * Base of the rules that turn an operand parsed as a type name into an
 * expression, when the name is declared as other than a type.
 *
 * @template TNode of NodeInterface
 *
 * @template-implements TransformerRuleInterface<TNode>
 */
abstract class ExpressionOperandRule implements TransformerRuleInterface
{
    /**
     * @return IdentifierExpression|null the name as an expression, {@see null}
     *         when the type is not a name declared as other than a type
     */
    protected function findExpression(TypeInterface $type, TransformationContext $context): ?IdentifierExpression
    {
        if (!$type instanceof NamedType || !$context->isNonTypeName($type->name->value)) {
            return null;
        }

        return new IdentifierExpression($type->name, $type->offset);
    }
}
