<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\TransformationContext;

/**
 * Ordinary identifiers declared so far: the typedef names and the other
 * names (objects, functions, enumeration constants, parameters), in the
 * file scope and in the function prototype scopes that enclose the node
 * being transformed.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.2.1 Scopes of identifiers
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Transformer
 */
final class Scope
{
    /**
     * The scopes from the file scope to the innermost one, each one maps a
     * name to whether it is a typedef name
     *
     * @var non-empty-list<array<string, bool>>
     */
    private array $scopes = [[]];

    /**
     * Opens a function prototype scope.
     */
    public function enter(): void
    {
        $this->scopes[] = [];
    }

    /**
     * Closes the innermost function prototype scope.
     */
    public function leave(): void
    {
        if (\count($this->scopes) > 1) {
            \array_pop($this->scopes);
        }
    }

    /**
     * Declares the name as a typedef name in the innermost scope.
     */
    public function declareTypedefName(string $name): void
    {
        $this->scopes[\array_key_last($this->scopes)][$name] = true;
    }

    /**
     * Declares the name as an object, a function, an enumeration constant
     * or a parameter in the innermost scope.
     */
    public function declareNonTypeName(string $name): void
    {
        $this->scopes[\array_key_last($this->scopes)][$name] = false;
    }

    public function isTypedefName(string $name): bool
    {
        return $this->findIsTypedefName($name) === true;
    }

    public function isNonTypeName(string $name): bool
    {
        return $this->findIsTypedefName($name) === false;
    }

    /**
     * @return bool|null whether the innermost declaration of the name is a
     *         typedef name, {@see null} in case of the name is not declared
     */
    private function findIsTypedefName(string $name): ?bool
    {
        for ($i = \count($this->scopes) - 1; $i >= 0; --$i) {
            if (isset($this->scopes[$i][$name])) {
                return $this->scopes[$i][$name];
            }
        }

        return null;
    }
}
