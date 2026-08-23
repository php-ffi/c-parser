<?php

declare(strict_types=1);

namespace FFI\Parser\Tests;

use FFI\Parser\Node\Attribute\Attribute;
use FFI\Parser\Node\Attribute\Attribute\ArgumentList\BalancedTokenSequence;
use FFI\Parser\Node\Attribute\DeclSpecAttribute;
use FFI\Parser\Node\Attribute\GnuAttribute;
use FFI\Parser\Node\Attribute\KeywordAttribute;
use FFI\Parser\Node\Attribute\StandardAttribute;
use FFI\Parser\Node\Declaration\AttributeDeclaration;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\AsmLabel;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\FunctionDefinition\FunctionBody;
use FFI\Parser\Node\Declaration\StaticAssertDeclaration;
use FFI\Parser\Node\Expression\AlignOfExpression;
use FFI\Parser\Node\Expression\AlignOfTypeExpression;
use FFI\Parser\Node\Expression\BinaryExpression;
use FFI\Parser\Node\Expression\CastExpression;
use FFI\Parser\Node\Expression\CompoundLiteralExpression;
use FFI\Parser\Node\Expression\ConditionalExpression;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Expression\MemberExpression;
use FFI\Parser\Node\Expression\MemberExpression\PointerMemberExpression;
use FFI\Parser\Node\Expression\OffsetOfExpression;
use FFI\Parser\Node\Expression\SizeOfExpression;
use FFI\Parser\Node\Expression\SizeOfTypeExpression;
use FFI\Parser\Node\Expression\SubscriptExpression;
use FFI\Parser\Node\Expression\UnaryExpression;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Initializer\InitializerList;
use FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer;
use FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer\Designator\IndexDesignator;
use FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer\Designator\MemberDesignator;
use FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer\Designator\RangeDesignator;
use FFI\Parser\Node\Literal\ConcatenatedStringLiteral;
use FFI\Parser\Node\Literal\Literal;
use FFI\Parser\Node\Node;
use FFI\Parser\Node\NodeList;
use FFI\Parser\Node\Specifier\AlignmentSpecifier\AlignAsSpecifier;
use FFI\Parser\Node\Specifier\AlignmentSpecifier\AlignAsTypeSpecifier;
use FFI\Parser\Node\Specifier\FunctionSpecifier;
use FFI\Parser\Node\Specifier\StorageClassSpecifier;
use FFI\Parser\Node\Type\ArrayType;
use FFI\Parser\Node\Type\AtomicType;
use FFI\Parser\Node\Type\AttributedType;
use FFI\Parser\Node\Type\BuiltinType;
use FFI\Parser\Node\Type\EnumType;
use FFI\Parser\Node\Type\EnumType\Enumerator;
use FFI\Parser\Node\Type\FunctionType;
use FFI\Parser\Node\Type\FunctionType\Parameter;
use FFI\Parser\Node\Type\ModifiedType;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\PointerType;
use FFI\Parser\Node\Type\QualifiedType;
use FFI\Parser\Node\Type\RecordType;
use FFI\Parser\Node\Type\RecordType\MemberDeclaration;
use FFI\Parser\Node\Type\SpecifiedType;
use FFI\Parser\Node\Type\TypeOfType;
use FFI\Parser\Node\Type\TypeOfTypeType;
use FFI\Parser\Node\Type\TypeOfUnqualType;
use FFI\Parser\Node\Type\TypeOfUnqualTypeType;

/**
 * Prints a syntax tree as an S-expression, the whole tree as one string:
 * `(+ 1 (* 2 3))`, `[const (unsigned long _)]`,
 * `struct S { int a }`, `(fn (ptr char) (int a, ...))`. A declaration is
 * named by its kind, `(variable static int x = {1, .a=2, [3]={}})`,
 * `(function ...)`, `(typedef ...)`, `(tag ...)`, `(type ...)`,
 * `(attributes ...)`. An attribute is printed in its own syntax with one
 * attribute per specifier, `[[gnu::aligned(8)]] __attribute__((packed))`,
 * attributes in a declarator wrap the type, `(attr _Nonnull (ptr int))`,
 * an argument that is no expression is `(tokens introduced=10.4)`. The
 * attributes among the specifiers are printed before the type and the ones
 * of a tag after its keyword wherever they are written, only the offsets
 * tell the places apart.
 * With the offsets every node ends with its offset: `(+ 1@14 2@18)@14`. A
 * missing node is `_`.
 */
final class NodePrinter
{
    /**
     * The spelling of the operator of every binary expression node.
     *
     * @var array<class-string<BinaryExpression>, non-empty-string>
     */
    public const BINARY_OPERATORS = [
        BinaryExpression\MulExpression::class => '*',
        BinaryExpression\DivExpression::class => '/',
        BinaryExpression\RemExpression::class => '%',
        BinaryExpression\AddExpression::class => '+',
        BinaryExpression\SubExpression::class => '-',
        BinaryExpression\ShiftLeftExpression::class => '<<',
        BinaryExpression\ShiftRightExpression::class => '>>',
        BinaryExpression\LessExpression::class => '<',
        BinaryExpression\GreaterExpression::class => '>',
        BinaryExpression\LessOrEqualExpression::class => '<=',
        BinaryExpression\GreaterOrEqualExpression::class => '>=',
        BinaryExpression\EqualExpression::class => '==',
        BinaryExpression\NotEqualExpression::class => '!=',
        BinaryExpression\BitwiseAndExpression::class => '&',
        BinaryExpression\BitwiseXorExpression::class => '^',
        BinaryExpression\BitwiseOrExpression::class => '|',
        BinaryExpression\LogicalAndExpression::class => '&&',
        BinaryExpression\LogicalOrExpression::class => '||',
        BinaryExpression\CommaExpression::class => ',',
    ];

    /**
     * The spelling of the operator of every unary expression node.
     *
     * @var array<class-string<UnaryExpression>, non-empty-string>
     */
    public const UNARY_OPERATORS = [
        UnaryExpression\PlusExpression::class => '+',
        UnaryExpression\MinusExpression::class => '-',
        UnaryExpression\LogicalNotExpression::class => '!',
        UnaryExpression\BitwiseNotExpression::class => '~',
        UnaryExpression\DereferenceExpression::class => '*',
        UnaryExpression\AddressOfExpression::class => '&',
    ];

    /**
     * The keyword of every node spelled by one.
     *
     * @var array<class-string<Node>, non-empty-string>
     */
    private const KEYWORDS = [
        BuiltinType\VoidType::class => 'void',
        BuiltinType\CharType::class => 'char',
        BuiltinType\IntType::class => 'int',
        BuiltinType\FloatType::class => 'float',
        BuiltinType\DoubleType::class => 'double',
        BuiltinType\BoolType::class => 'bool',
        BuiltinType\Decimal32Type::class => '_Decimal32',
        BuiltinType\Decimal64Type::class => '_Decimal64',
        BuiltinType\Decimal128Type::class => '_Decimal128',
        BuiltinType\Int8Type::class => '__int8',
        BuiltinType\Int16Type::class => '__int16',
        BuiltinType\Int32Type::class => '__int32',
        BuiltinType\Int64Type::class => '__int64',
        BuiltinType\Int128Type::class => '__int128',
        StorageClassSpecifier\TypeDefSpecifier::class => 'typedef',
        StorageClassSpecifier\ExternSpecifier::class => 'extern',
        StorageClassSpecifier\StaticSpecifier::class => 'static',
        StorageClassSpecifier\ThreadLocalSpecifier::class => 'thread_local',
        StorageClassSpecifier\AutoSpecifier::class => 'auto',
        StorageClassSpecifier\RegisterSpecifier::class => 'register',
        StorageClassSpecifier\ConstExprSpecifier::class => 'constexpr',
        FunctionSpecifier\InlineSpecifier::class => 'inline',
        FunctionSpecifier\NoReturnSpecifier::class => '_Noreturn',
    ];

    public static function print(?Node $node): string
    {
        return (new self(false))->format($node);
    }

    public static function printWithOffsets(?Node $node): string
    {
        return (new self(true))->format($node);
    }

    private function __construct(
        private readonly bool $withOffsets,
    ) {}

    private function format(?Node $node): string
    {
        if ($node === null) {
            return '_';
        }

        // A list is not marked with its offset, which is the one of its
        // first node
        if ($node instanceof NodeList && !$node instanceof InitializerList) {
            return \implode(' ', \array_map($this->format(...), $node->items));
        }

        $body = match (true) {
            isset(self::KEYWORDS[$node::class]) => self::KEYWORDS[$node::class],
            $node instanceof Literal => $node->raw,
            $node instanceof Identifier => $node->name,
            $node instanceof IdentifierExpression, $node instanceof NamedType => $node->name->name,
            $node instanceof BinaryExpression => \sprintf(
                '(%s %s %s)',
                self::BINARY_OPERATORS[$node::class],
                $this->format($node->left),
                $this->format($node->right),
            ),
            $node instanceof UnaryExpression => \sprintf(
                '(%su %s)',
                self::UNARY_OPERATORS[$node::class],
                $this->format($node->operand),
            ),
            $node instanceof ConditionalExpression => \sprintf(
                '(? %s %s %s)',
                $this->format($node->condition),
                $this->format($node->then),
                $this->format($node->else),
            ),
            $node instanceof CastExpression => \sprintf(
                '(cast %s %s)',
                $this->format($node->type),
                $this->format($node->operand),
            ),
            $node instanceof SizeOfExpression => '(sizeof ' . $this->format($node->operand) . ')',
            $node instanceof CompoundLiteralExpression => \sprintf(
                '(compound %s %s)',
                $this->format($node->type),
                $this->format($node->initializer),
            ),
            $node instanceof SizeOfTypeExpression => '(sizeof ' . $this->format($node->type) . ')',
            $node instanceof AlignOfTypeExpression => '(alignof ' . $this->format($node->type) . ')',
            $node instanceof AlignOfExpression => '(alignof ' . $this->format($node->operand) . ')',
            $node instanceof OffsetOfExpression => \sprintf(
                '(offsetof %s %s)',
                $this->format($node->type),
                \implode('', \array_map($this->format(...), $node->path->items)),
            ),
            $node instanceof SubscriptExpression => \sprintf(
                '([] %s %s)',
                $this->format($node->array),
                $this->format($node->index),
            ),
            $node instanceof MemberExpression => \sprintf(
                '(%s %s %s)',
                $node instanceof PointerMemberExpression ? '->' : '.',
                $this->format($node->object),
                $this->format($node->member),
            ),
            $node instanceof ConcatenatedStringLiteral => \sprintf(
                '(concat %s)',
                \implode(' ', \array_map($this->format(...), $node->parts)),
            ),
            $node instanceof BuiltinType\BitIntType => '_BitInt(' . $this->format($node->width) . ')',
            $node instanceof AtomicType => '_Atomic(' . $this->format($node->type) . ')',
            $node instanceof TypeOfType => 'typeof(' . $this->format($node->operand) . ')',
            $node instanceof TypeOfTypeType => 'typeof(' . $this->format($node->type) . ')',
            $node instanceof TypeOfUnqualType => 'typeof_unqual(' . $this->format($node->operand) . ')',
            $node instanceof TypeOfUnqualTypeType => 'typeof_unqual(' . $this->format($node->type) . ')',
            $node instanceof ModifiedType => \sprintf(
                '(%s %s)',
                \implode(' ', \array_map(static fn($modifier): string => $modifier->value, $node->modifiers)),
                $this->format($node->type),
            ),
            $node instanceof QualifiedType => \sprintf(
                '[%s %s]',
                \implode(' ', \array_map(static fn($qualifier): string => $qualifier->value, $node->qualifiers)),
                $this->format($node->type),
            ),
            $node instanceof SpecifiedType => \sprintf(
                '{%s %s}',
                $this->format($node->specifiers),
                $this->format($node->type),
            ),
            $node instanceof AlignAsSpecifier => 'alignas(' . $this->format($node->operand) . ')',
            $node instanceof AlignAsTypeSpecifier => 'alignas(' . $this->format($node->type) . ')',
            $node instanceof PointerType => \sprintf(
                '(ptr %s%s)',
                $this->format($node->type),
                $node->attributes === null ? '' : ' ' . $this->format($node->attributes),
            ),
            $node instanceof AttributedType => \sprintf(
                '(attr %s %s)',
                $this->format($node->attributes),
                $this->format($node->type),
            ),
            $node instanceof StandardAttribute => \sprintf(
                '[[%s%s%s]]',
                $node->prefix === null ? '' : $this->format($node->prefix) . '::',
                $this->format($node->name),
                $this->formatArguments($node),
            ),
            $node instanceof GnuAttribute => \sprintf(
                '__attribute__((%s%s))',
                $this->format($node->name),
                $this->formatArguments($node),
            ),
            $node instanceof DeclSpecAttribute => \sprintf(
                '__declspec(%s%s)',
                $this->format($node->name),
                $this->formatArguments($node),
            ),
            $node instanceof KeywordAttribute => $this->format($node->name),
            $node instanceof BalancedTokenSequence => '(tokens ' . $node->raw . ')',
            $node instanceof ArrayType => \sprintf(
                '(array %s)',
                \implode(' ', [
                    $this->format($node->type),
                    ...($node instanceof ArrayType\SizedArrayType && $node->isStatic ? ['static'] : []),
                    ...\array_map(static fn($qualifier): string => $qualifier->value, $node->qualifiers),
                    ...match (true) {
                        $node instanceof ArrayType\SizedArrayType => [$this->format($node->size)],
                        $node instanceof ArrayType\UnspecifiedSizeArrayType => ['*'],
                        default => [],
                    },
                    ...($node->attributes === null ? [] : [$this->format($node->attributes)]),
                ]),
            ),
            $node instanceof FunctionType => \sprintf(
                '(fn %s (%s)%s)',
                $this->format($node->returnType),
                \implode(', ', [
                    ...\array_map($this->format(...), $node->parameters->items),
                    ...($node->isVariadic ? ['...'] : []),
                ]),
                $node->attributes === null ? '' : ' ' . $this->format($node->attributes),
            ),
            $node instanceof Parameter => \implode(' ', \array_filter([
                $this->format($node->specifiers),
                $this->format($node->type),
                $node->name === null ? '' : $this->format($node->name),
                $node->attributes === null ? '' : $this->format($node->attributes),
            ])),
            $node instanceof RecordType => \sprintf(
                '%s %s%s%s',
                $node instanceof RecordType\UnionType ? 'union' : 'struct',
                $node->attributes === null ? '' : $this->format($node->attributes) . ' ',
                $node->name->name ?? '_',
                $node->members === null ? '' : ' { ' . \implode('; ', \array_map(
                    $this->format(...),
                    $node->members->items,
                )) . ' }',
            ),
            $node instanceof MemberDeclaration => \implode(' ', \array_filter([
                $this->format($node->specifiers),
                $this->format($node->type),
                ($node->name->name ?? '_') . ($node->width === null ? '' : ':' . $this->format($node->width)),
                $node->attributes === null ? '' : $this->format($node->attributes),
            ])),
            $node instanceof EnumType => \sprintf(
                'enum %s%s%s%s',
                $node->attributes === null ? '' : $this->format($node->attributes) . ' ',
                $node->name->name ?? '_',
                $node->underlyingType === null ? '' : ' : ' . $this->format($node->underlyingType),
                $node->enumerators === null ? '' : ' { ' . \implode(', ', \array_map(
                    $this->format(...),
                    $node->enumerators->items,
                )) . ' }',
            ),
            $node instanceof Enumerator => $node->name->name
                . ($node->attributes === null ? '' : ' ' . $this->format($node->attributes))
                . ($node->value === null ? '' : '=' . $this->format($node->value)),
            $node instanceof StaticAssertDeclaration => \sprintf(
                '(static_assert %s%s)',
                $this->format($node->condition),
                $node->message === null ? '' : ' ' . $this->format($node->message),
            ),
            $node instanceof SpecifiedDeclaration\TagDeclaration,
            $node instanceof SpecifiedDeclaration\TypeDeclaration => \sprintf(
                '(%s %s)',
                $node instanceof SpecifiedDeclaration\TagDeclaration ? 'tag' : 'type',
                \implode(' ', \array_filter([$this->format($node->specifiers), $this->format($node->type)])),
            ),
            $node instanceof SpecifiedDeclaration\DeclaratorDeclaration => \sprintf(
                '(%s %s)',
                match (true) {
                    $node instanceof SpecifiedDeclaration\DeclaratorDeclaration\TypeDefDeclaration => 'typedef',
                    $node instanceof SpecifiedDeclaration\DeclaratorDeclaration\FunctionDeclaration => 'function',
                    $node instanceof SpecifiedDeclaration\DeclaratorDeclaration\FunctionDefinition => 'definition',
                    default => 'variable',
                },
                \implode(' ', \array_filter([
                    $this->format($node->specifiers),
                    $this->format($node->type ?? null),
                    $this->format($node->name),
                    $node->attributes === null ? '' : $this->format($node->attributes),
                    $node->asmLabel === null ? '' : $this->format($node->asmLabel),
                    $node->initializer === null ? '' : '= ' . $this->format($node->initializer),
                    $node instanceof SpecifiedDeclaration\DeclaratorDeclaration\FunctionDefinition
                        ? $this->format($node->body)
                        : '',
                ])),
            ),
            $node instanceof FunctionBody => $node->raw,
            $node instanceof AsmLabel => 'asm(' . $this->format($node->name) . ')',
            $node instanceof AttributeDeclaration => '(attributes ' . $this->format($node->attributes) . ')',
            $node instanceof InitializerList => '{' . \implode(', ', \array_map(
                $this->format(...),
                $node->items,
            )) . '}',
            $node instanceof DesignatedInitializer => \implode('', \array_map(
                $this->format(...),
                $node->designators->items,
            )) . '=' . $this->format($node->initializer),
            $node instanceof MemberDesignator => '.' . $this->format($node->member),
            $node instanceof IndexDesignator => '[' . $this->format($node->index) . ']',
            $node instanceof RangeDesignator => \sprintf(
                '[%s ... %s]',
                $this->format($node->first),
                $this->format($node->last),
            ),
            default => throw new \LogicException(\sprintf('%s cannot be printed', $node::class)),
        };

        return $this->withOffsets ? $body . '@' . $node->offset : $body;
    }

    private function formatArguments(Attribute $attribute): string
    {
        if ($attribute->arguments === null) {
            return '';
        }

        return '(' . \implode(', ', \array_map($this->format(...), $attribute->arguments->items)) . ')';
    }
}
