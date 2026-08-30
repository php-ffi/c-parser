<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\GenericSelectionExpression;

use FFI\Parser\Node\NodeList;

/**
 * Associations of a generic selection, the list after its controlling
 * operand.
 *
 * @template-extends NodeList<GenericAssociation>
 */
final class GenericAssociationList extends NodeList {}
