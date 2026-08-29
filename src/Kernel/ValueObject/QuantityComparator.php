<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

/**
 * Comparison modifier of a quantity, per FHIR Quantity.comparator:
 * < | <= | >= | > (e.g. "< 5 ng/L").
 */
enum QuantityComparator: string
{
    case LESS_THAN = '<';
    case LESS_THAN_OR_EQUAL = '<=';
    case GREATER_THAN_OR_EQUAL = '>=';
    case GREATER_THAN = '>';
}
