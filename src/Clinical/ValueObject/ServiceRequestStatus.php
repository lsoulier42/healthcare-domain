<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

/**
 * Lifecycle status of a service request (FHIR-inspired).
 */
enum ServiceRequestStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case ON_HOLD = 'on-hold';
    case REVOKED = 'revoked';
    case COMPLETED = 'completed';
    case ENTERED_IN_ERROR = 'entered-in-error';
    case UNKNOWN = 'unknown';
}
