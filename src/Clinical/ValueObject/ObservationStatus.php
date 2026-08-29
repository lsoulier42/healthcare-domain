<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

/**
 * Lifecycle status of an observation (FHIR-inspired).
 */
enum ObservationStatus: string
{
    case REGISTERED = 'registered';
    case PRELIMINARY = 'preliminary';
    case FINAL = 'final';
    case AMENDED = 'amended';
    case CORRECTED = 'corrected';
    case CANCELLED = 'cancelled';
    case ENTERED_IN_ERROR = 'entered-in-error';
    case UNKNOWN = 'unknown';
}
