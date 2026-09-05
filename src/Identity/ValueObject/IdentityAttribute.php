<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

/**
 * Optional attributes a French identity record can carry, alongside the four
 * exclusive RNIV functional statuses (see {@see IdentityStatus}).
 *
 * The RNIV (référentiel national d'identitovigilance) defines three distinct
 * attributes that characterize identities requiring special handling:
 *
 * - HOMONYM   — « identité homonyme »: high-resemblance identities requiring
 *               extra vigilance; compatible with every status.
 * - DOUBTFUL  — « identité douteuse »: a doubt exists on the veracity of the
 *               collected identity; only compatible with the PROVISIONAL
 *               status, and calling the INSi téléservice is forbidden.
 * - FICTITIOUS— « identité fictive »: sensitive or imaginary identities
 *               (tests, training, protected pathways); only compatible with
 *               the PROVISIONAL status.
 *
 * Factual references:
 * - Guide d'implémentation INS dans les logiciels v3.0 (DNS, 12/2024),
 *   exigence [EXI ID 24] (attributs homonyme / douteux / fictif) et
 *   [EXI ID 26] (règles de gestion associées : attribution d'un statut autre
 *   que provisoire rendue impossible, appel au téléservice INSi bloqué,
 *   invalidation du matricule INS et de son OID).
 */
enum IdentityAttribute: string
{
    case HOMONYM = 'homonyme';
    case DOUBTFUL = 'douteux';
    case FICTITIOUS = 'fictif';

    public function label(): string
    {
        return match ($this) {
            self::HOMONYM => 'Identité homonyme',
            self::DOUBTFUL => 'Identité douteuse',
            self::FICTITIOUS => 'Identité fictive',
        };
    }

    /**
     * Whether this attribute can coexist with another one.
     *
     * Per [EXI ID 24] / RNIV: « Seuls les attributs douteux et homonymes
     * peuvent être utilisés simultanément » — DOUBTFUL + HOMONYM is the only
     * authorized combination. The « fictif » and « douteux » attributes
     * cannot be cumulated, nor can « fictif » be combined with « homonyme ».
     */
    public function combinesWith(self $other): bool
    {
        return ($this === self::DOUBTFUL && $other === self::HOMONYM)
            || ($this === self::HOMONYM && $other === self::DOUBTFUL);
    }

    /**
     * Whether the attribute forces the identity to the PROVISIONAL status and
     * forbids any INSi téléservice call, per [EXI ID 26].
     */
    public function restrictsToProvisionalStatus(): bool
    {
        return $this === self::DOUBTFUL || $this === self::FICTITIOUS;
    }
}
