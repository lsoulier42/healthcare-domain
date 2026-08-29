<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

use Healthcare\Identity\ValueObject\HumanName;
use Healthcare\Kernel\ValueObject\Adeli;
use Healthcare\Kernel\ValueObject\Rpps;

/**
 * Stable professional identity of a healthcare practitioner, common to
 * healthcare applications. This is identity semantics only: no
 * application/database ID, no organization membership, no specialty or
 * role, no address or contact, no application state.
 *
 * RPPS and ADELI are optional: imported, manual or foreign practitioner
 * records may be incomplete while still having a representable identity.
 */
final readonly class PractitionerIdentity
{
    public function __construct(
        public HumanName $name,
        public ?Rpps $rpps = null,
        public ?Adeli $adeli = null,
    ) {
    }

    public function equals(self $other): bool
    {
        $sameRpps = $this->rpps === null
            ? $other->rpps === null
            : $other->rpps !== null && $this->rpps->equals($other->rpps);

        $sameAdeli = $this->adeli === null
            ? $other->adeli === null
            : $other->adeli !== null && $this->adeli->equals($other->adeli);

        return $this->name->equals($other->name) && $sameRpps && $sameAdeli;
    }
}
