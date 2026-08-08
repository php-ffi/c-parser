<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Grammar;

use FFI\Parser\Exception\SyntaxException;
use FFI\Parser\Node\Declaration\StaticAssertDeclaration;
use FFI\Parser\Node\Expression\AlignofExpression;
use FFI\Parser\Node\Expression\BinaryExpression;
use FFI\Parser\Node\Expression\CastExpression;
use FFI\Parser\Node\Expression\ConditionalExpression;
use FFI\Parser\Node\Expression\IdentifierExpression;
use FFI\Parser\Node\Expression\MemberExpression;
use FFI\Parser\Node\Expression\MemberExpression\PointerMemberExpression;
use FFI\Parser\Node\Expression\SizeofExpression;
use FFI\Parser\Node\Expression\SizeofTypeExpression;
use FFI\Parser\Node\Expression\SubscriptExpression;
use FFI\Parser\Node\Expression\UnaryExpression;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Literal\BoolLiteral;
use FFI\Parser\Node\Literal\CharLiteral;
use FFI\Parser\Node\Literal\ConcatenatedStringLiteral;
use FFI\Parser\Node\Literal\FloatLiteral;
use FFI\Parser\Node\Literal\IntLiteral;
use FFI\Parser\Node\Literal\Literal;
use FFI\Parser\Node\Literal\NullptrLiteral;
use FFI\Parser\Node\Literal\StringLiteral;
use FFI\Parser\Node\Node;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Parser;
use FFI\Parser\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks the constant expressions of `resources/grammar/expressions.pp3`. An
 * expression is parsed as the condition of a `static_assert` and compared as
 * an S-expression with the offset of every node: `(+ 1@14 2@18)@14`, the
 * condition starts at the offset 14, right after `static_assert(`.
 */
#[CoversNothing]
final class ExpressionsTest extends TestCase
{
    /**
     * The spelling of the operator of every binary expression node.
     *
     * @var array<class-string<BinaryExpression>, non-empty-string>
     */
    private const BINARY_OPERATORS = [
        BinaryExpression\MulExpression::class => '*',
        BinaryExpression\DivExpression::class => '/',
        BinaryExpression\ModExpression::class => '%',
        BinaryExpression\AddExpression::class => '+',
        BinaryExpression\SubExpression::class => '-',
        BinaryExpression\ShlExpression::class => '<<',
        BinaryExpression\ShrExpression::class => '>>',
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
    private const UNARY_OPERATORS = [
        UnaryExpression\PlusExpression::class => '+',
        UnaryExpression\MinusExpression::class => '-',
        UnaryExpression\LogicalNotExpression::class => '!',
        UnaryExpression\BitwiseNotExpression::class => '~',
        UnaryExpression\DereferenceExpression::class => '*',
        UnaryExpression\AddressOfExpression::class => '&',
    ];

    /**
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function expressionsDataProvider(): iterable
    {
        yield 'identifier' => ['a', 'a@14'];
        yield 'parenthesized' => ['(a)', 'a@15'];
        yield 'operation on a parenthesized operand starts at the parenthesis' => ['(a) + b', '(+ a@15 b@20)@14'];
        yield 'conditional on a parenthesized operand' => ['(a) ? b : c', '(? a@15 b@20 c@24)@14'];
        yield 'integer constant' => ['0x1Fu', '0x1Fu@14'];
        yield 'floating constant' => ['1.5f', '1.5f@14'];
        yield 'character constant' => ["'a'", "'a'@14"];
        yield 'string literal' => ['"a"', '"a"@14'];
        yield 'adjacent string literals' => ['"a" L"b"', '(concat "a"@14 L"b"@18)@14'];
        yield 'three adjacent string literals' => ['"a" "b" "c"', '(concat "a"@14 "b"@18 "c"@22)@14'];
        yield 'cast of adjacent string literals' => ['(T)"a" "b"', '(cast type:T@15 (concat "a"@17 "b"@21)@17)@14'];
        yield 'sizeof of adjacent string literals' => ['sizeof "a" "b"', '(sizeof (concat "a"@21 "b"@25)@21)@14'];
        yield 'true' => ['true', 'true@14'];
        yield 'false' => ['false', 'false@14'];
        yield 'nullptr' => ['nullptr', 'nullptr@14'];

        // Precedence and associativity
        yield 'left associativity' => ['1 - 2 - 3', '(- (- 1@14 2@18)@14 3@22)@14'];
        yield 'multiplication binds tighter' => ['1 + 2 * 3', '(+ 1@14 (* 2@18 3@22)@18)@14'];
        yield 'parentheses override precedence' => ['(1 + 2) * 3', '(* (+ 1@15 2@19)@15 3@24)@14'];
        yield 'unary binds tighter' => ['-x * y', '(* (-u x@15)@14 y@19)@14'];
        yield 'logical and binds tighter' => ['a || b && c', '(|| a@14 (&& b@19 c@24)@19)@14'];
        yield 'equality binds tighter than bitwise and' => ['a & b == c', '(& a@14 (== b@18 c@23)@18)@14'];
        yield 'shift binds looser than addition' => ['a << b + c', '(<< a@14 (+ b@19 c@23)@19)@14'];
        yield 'every level' => [
            'a || b && c | d ^ e & f == g < h << i + j * k',
            '(|| a@14 (&& b@19 (| c@24 (^ d@28 (& e@32 (== f@36 (< g@41 (<< h@45 (+ i@50 (* j@54 k@58)@54)@50)@45)'
                . '@41)@36)@32)@28)@24)@19)@14',
        ];
        yield 'conditional is right associative' => ['a ? b : c ? d : e', '(? a@14 b@18 (? c@22 d@26 e@30)@22)@14'];
        yield 'conditional binds looser than logical or' => ['a || b ? c : d', '(? (|| a@14 b@19)@14 c@23 d@27)@14'];
        yield 'comma between ? and :' => ['a ? b, c : d', '(? a@14 (, b@18 c@21)@18 d@25)@14'];
        yield 'comma in parentheses' => ['(a, b, c)', '(, (, a@15 b@18)@15 c@21)@15'];

        // Unary operators
        yield 'nested unary operators' => ['-~!x', '(-u (~u (!u x@17)@16)@15)@14'];
        yield 'address of an element' => ['&arr[2]', '(&u ([] arr@15 2@19)@15)@14'];
        yield 'dereference of a member' => ['*p->q', '(*u (-> p@15 q@18)@15)@14'];

        // Cast against parentheses
        yield 'cast of a name' => ['(T)x', '(cast type:T@15 x@17)@14'];
        yield 'cast of a constant' => ['(T)1', '(cast type:T@15 1@17)@14'];
        yield 'cast of a parenthesized operand' => ['(T)(x)', '(cast type:T@15 x@18)@14'];
        yield 'cast of a negation' => ['(T)~x', '(cast type:T@15 (~u x@18)@17)@14'];
        yield 'cast of sizeof' => ['(T)sizeof x', '(cast type:T@15 (sizeof x@24)@17)@14'];
        yield 'cast of alignof' => ['(T)alignof(U)', '(cast type:T@15 (alignof type:U@25)@17)@14'];
        yield 'cast of a logical negation' => ['(T)!x', '(cast type:T@15 (!u x@18)@17)@14'];
        yield 'cast of a floating constant' => ['(T)1.5', '(cast type:T@15 1.5@17)@14'];
        yield 'cast of a character constant' => ["(T)'a'", "(cast type:T@15 'a'@17)@14"];
        yield 'cast of a string literal' => ['(T)"a"', '(cast type:T@15 "a"@17)@14'];
        yield 'cast of true' => ['(T)true', '(cast type:T@15 true@17)@14'];
        yield 'cast of false' => ['(T)false', '(cast type:T@15 false@17)@14'];
        yield 'cast of nullptr' => ['(T)nullptr', '(cast type:T@15 nullptr@17)@14'];
        yield 'plus after parentheses is an addition' => ['(T)+x', '(+ T@15 x@18)@14'];
        yield 'ampersand after parentheses is a bitwise and' => ['(T)&x', '(& T@15 x@18)@14'];
        yield 'nested casts' => ['(A)(B)x', '(cast type:A@15 (cast type:B@18 x@20)@17)@14'];
        yield 'minus after parentheses is a subtraction' => ['(T)-x', '(- T@15 x@18)@14'];
        yield 'asterisk after parentheses is a multiplication' => ['(T)*p', '(* T@15 p@18)@14'];
        yield 'subscript after parentheses' => ['(x)[0]', '([] x@15 0@18)@14'];
        yield 'member after parentheses' => ['(x).a', '(. x@15 a@18)@14'];

        // Postfix operators
        yield 'chain of postfix operators' => ['p->m.n[2]', '([] (. (-> p@14 m@17)@14 n@19)@14 2@21)@14'];
        yield 'digraph subscript' => ['a<:1:>', '([] a@14 1@17)@14'];
        yield 'comma in a subscript' => ['a[1, 2]', '([] a@14 (, 1@16 2@19)@16)@14'];

        // sizeof and alignof
        yield 'sizeof of an expression' => ['sizeof x', '(sizeof x@21)@14'];
        yield 'sizeof of a type' => ['sizeof(T)', '(sizeof type:T@21)@14'];
        yield 'sizeof of a subscript in parentheses' => ['sizeof(x)[0]', '(sizeof ([] x@21 0@24)@20)@14'];
        yield 'sizeof of a parenthesized expression' => ['sizeof(1 + 2)', '(sizeof (+ 1@21 2@25)@21)@14'];
        yield 'sizeof of sizeof' => ['sizeof sizeof x', '(sizeof (sizeof x@28)@21)@14'];
        yield 'sizeof in a sum' => ['sizeof(T) + 1', '(+ (sizeof type:T@21)@14 1@26)@14'];
        yield 'alignof' => ['alignof(T)', '(alignof type:T@22)@14'];
        yield '_Alignof' => ['_Alignof(T)', '(alignof type:T@23)@14'];
    }

    #[DataProvider('expressionsDataProvider')]
    public function testExpressionIsParsed(string $expression, string $expected): void
    {
        self::assertSame($expected, self::dump(self::parseCondition($expression)));
        self::assertTrue((new Parser())->validate('static_assert(' . $expression . ');'));
    }

    public function testNameInAnExpressionRefersToTheIdentifier(): void
    {
        $node = self::parseCondition('(a)');

        self::assertInstanceOf(IdentifierExpression::class, $node);
        self::assertSame('a', $node->name->name);
        self::assertSame(15, $node->name->offset);
    }

    public function testMemberIsNamedByTheIdentifier(): void
    {
        $node = self::parseCondition('a->b');

        self::assertInstanceOf(PointerMemberExpression::class, $node);
        self::assertInstanceOf(IdentifierExpression::class, $node->object);
        self::assertSame('b', $node->member->name);
        self::assertSame(17, $node->member->offset);
    }

    /**
     * @return iterable<non-empty-string, array{class-string<BinaryExpression>}>
     */
    public static function binaryExpressionsDataProvider(): iterable
    {
        foreach (self::BINARY_OPERATORS as $class => $operator) {
            yield 'binary ' . $operator => [$class];
        }
    }

    /**
     * @param class-string<BinaryExpression> $class
     */
    #[DataProvider('binaryExpressionsDataProvider')]
    public function testBinaryOperatorBuildsItsNode(string $class): void
    {
        $operator = self::BINARY_OPERATORS[$class];
        $node = self::parseCondition($operator === ',' ? '(a , b)' : 'a ' . $operator . ' b');

        self::assertInstanceOf($class, $node);
    }

    /**
     * @return iterable<non-empty-string, array{class-string<UnaryExpression>}>
     */
    public static function unaryExpressionsDataProvider(): iterable
    {
        foreach (self::UNARY_OPERATORS as $class => $operator) {
            yield 'unary ' . $operator => [$class];
        }
    }

    /**
     * @param class-string<UnaryExpression> $class
     */
    #[DataProvider('unaryExpressionsDataProvider')]
    public function testUnaryOperatorBuildsItsNode(string $class): void
    {
        self::assertInstanceOf($class, self::parseCondition(self::UNARY_OPERATORS[$class] . 'a'));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, class-string<Literal>}>
     */
    public static function literalsDataProvider(): iterable
    {
        yield 'integer constant' => ['1', IntLiteral::class];
        yield 'floating constant' => ['1.5', FloatLiteral::class];
        yield 'character constant' => ["'a'", CharLiteral::class];
        yield 'string literal' => ['"a"', StringLiteral::class];
        yield 'true' => ['true', BoolLiteral::class];
        yield 'false' => ['false', BoolLiteral::class];
        yield 'nullptr' => ['nullptr', NullptrLiteral::class];
    }

    /**
     * @param class-string<Literal> $class
     */
    #[DataProvider('literalsDataProvider')]
    public function testLiteralBuildsItsNode(string $expression, string $class): void
    {
        self::assertInstanceOf($class, self::parseCondition($expression));
    }

    public function testBoolLiteralsKeepTheirValues(): void
    {
        $true = self::parseCondition('true');
        $false = self::parseCondition('false');

        self::assertInstanceOf(BoolLiteral::class, $true);
        self::assertInstanceOf(BoolLiteral::class, $false);
        self::assertTrue($true->value);
        self::assertFalse($false->value);
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, int<0, max>}>
     */
    public static function invalidExpressionsDataProvider(): iterable
    {
        yield 'missing right operand' => ['1 +', 17];
        yield 'unclosed parenthesis' => ['(1', 17];
        yield 'conditional without else' => ['a ? b', 19];
        yield 'assignment' => ['a = 1', 16];
        yield 'leading assignment' => ['= 1', 14];
        yield 'function call' => ['f(1)', 15];
        yield 'increment' => ['a++', 15];
        yield 'sizeof without an operand' => ['sizeof', 20];
        yield 'alignof of an expression' => ['alignof x', 22];
        yield 'cast without an operand' => ['(T)~', 18];
        yield 'unclosed subscript' => ['a[1', 17];
        yield 'member without a name' => ['a.', 16];
        yield 'member named by a number' => ['a->1', 17];
        yield 'empty parentheses' => ['()', 15];
        yield 'operand after sizeof of a type' => ['sizeof(T)x', 23];
        yield 'comma outside parentheses' => ['a, b', 17];
    }

    #[DataProvider('invalidExpressionsDataProvider')]
    public function testInvalidExpressionIsRejected(string $expression, int $offset): void
    {
        $code = 'static_assert(' . $expression . ');';

        self::assertFalse((new Parser())->validate($code));

        try {
            (new Parser())->parse($code);
        } catch (SyntaxException $e) {
            self::assertSame($offset, $e->offset);

            return;
        }

        self::fail('SyntaxException has not been thrown');
    }

    /**
     * @param non-empty-string $expression
     */
    private static function parseCondition(string $expression): Node
    {
        $nodes = (new Parser())->parse('static_assert(' . $expression . ');');

        self::assertCount(1, $nodes);
        self::assertInstanceOf(StaticAssertDeclaration::class, $nodes[0]);

        return $nodes[0]->condition;
    }

    private static function dump(Node $node): string
    {
        $body = match (true) {
            $node instanceof BinaryExpression => \sprintf(
                '(%s %s %s)',
                self::BINARY_OPERATORS[$node::class],
                self::dump($node->left),
                self::dump($node->right),
            ),
            $node instanceof UnaryExpression => \sprintf(
                '(%su %s)',
                self::UNARY_OPERATORS[$node::class],
                self::dump($node->operand),
            ),
            $node instanceof ConditionalExpression => \sprintf(
                '(? %s %s %s)',
                self::dump($node->condition),
                self::dump($node->then),
                self::dump($node->else),
            ),
            $node instanceof CastExpression => \sprintf(
                '(cast %s %s)',
                self::dump($node->type),
                self::dump($node->operand),
            ),
            $node instanceof SizeofExpression => \sprintf('(sizeof %s)', self::dump($node->operand)),
            $node instanceof SizeofTypeExpression => \sprintf('(sizeof %s)', self::dump($node->type)),
            $node instanceof AlignofExpression => \sprintf('(alignof %s)', self::dump($node->type)),
            $node instanceof SubscriptExpression => \sprintf(
                '([] %s %s)',
                self::dump($node->array),
                self::dump($node->index),
            ),
            $node instanceof MemberExpression => \sprintf(
                '(%s %s %s)',
                $node instanceof PointerMemberExpression ? '->' : '.',
                self::dump($node->object),
                self::dump($node->member),
            ),
            $node instanceof ConcatenatedStringLiteral => \sprintf(
                '(concat %s)',
                \implode(' ', \array_map(self::dump(...), $node->parts)),
            ),
            $node instanceof IdentifierExpression => $node->name->name,
            $node instanceof Identifier => $node->name,
            $node instanceof NamedType => 'type:' . $node->name->name,
            $node instanceof Literal => $node->raw,
            default => self::fail(\sprintf('%s is not expected in a constant expression', $node::class)),
        };

        return $body . '@' . $node->offset;
    }
}
