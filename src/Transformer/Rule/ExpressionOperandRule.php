<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\Rule;

use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\TypeInterface;
use FFI\Parser\Transformer\TransformationContext;
use FFI\Parser\Transformer\TransformerRuleInterface;

/**
 * Base of the rules that turn an operand parsed as a type name into an
 * expression, when the type is a name declared as an object, a function,
 * an enumeration constant or a parameter.
 */
abstract class ExpressionOperandRule implements TransformerRuleInterface
{
    /**
     * @return IdentifierExpression|null the name as an expression,
     *         {@see null} in case of the type is not a name declared as
     *         other than a type
     */
    protected function findExpression(TypeInterface $type, TransformationContext $context): ?IdentifierExpression
    {
        if (!$type instanceof NamedType || !$context->isNonTypeName($type->name->value)) {
            return null;
        }

        return new IdentifierExpression($type->name, $type->offset);
    }
}
