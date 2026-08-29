<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

use Healthcare\Kernel\ValueObject\Oid;

/**
 * Authority responsible for assigning/interpreting an INS matricule,
 * identified by its official OID.
 *
 * Official authorities (per the ANS "Référentiel INS — liste des OID
 * des autorités d'affectation des INS" and the FR Core Patient INS
 * profile):
 *
 * - nir():     INS-NIR       1.2.250.1.213.1.4.8
 * - nia():     INS-NIA       1.2.250.1.213.1.4.9
 * - nirTest(): INS-NIR-TEST  1.2.250.1.213.1.4.10
 * - nirDemo(): INS-NIR-DEMO  1.2.250.1.213.1.4.11
 *
 * The authorities are deliberately not a closed enum: unknown or future
 * valid OIDs remain representable via the constructor.
 */
final readonly class InsAssigningAuthority
{
    public function __construct(
        public Oid $oid,
    ) {
    }

    public static function nir(): self
    {
        return new self(new Oid('1.2.250.1.213.1.4.8'));
    }

    public static function nia(): self
    {
        return new self(new Oid('1.2.250.1.213.1.4.9'));
    }

    public static function nirTest(): self
    {
        return new self(new Oid('1.2.250.1.213.1.4.10'));
    }

    public static function nirDemo(): self
    {
        return new self(new Oid('1.2.250.1.213.1.4.11'));
    }

    public function equals(self $other): bool
    {
        return $this->oid->equals($other->oid);
    }
}
