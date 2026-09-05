<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Immutable, unordered set of {@see IdentityAttribute} carried by an identity.
 *
 * The collection enforces at construction the combinability rule of the RNIV
 * ([EXI ID 24]) : « Seuls les attributs douteux et homonymes peuvent être
 * utilisés simultanément » — DOUBTFUL + HOMONYM is the only authorized
 * multi-attribute set; « fictif » combines with no other attribute.
 *
 * The collection also centralizes the status/invocation invariants tied to
 * these attributes ([EXI ID 26]) :
 *
 * - `requiresProvisionalStatus()` — when the set contains « douteux » or
 *   « fictif », the identity must stay (or fall back to) the PROVISIONAL status
 *   and its INS (matricule + OID) must be invalidated;
 * - `blocksInsiLookup()` — calling the INSi téléservice is forbidden for such
 *   identities.
 *
 * Factual reference: Guide d'implémentation INS dans les logiciels v3.0 (DNS,
 * 12/2024), exigences [EXI ID 24] et [EXI ID 26] ; RNIV.
 */
final readonly class IdentityAttributes
{
    /** @var list<IdentityAttribute> */
    public array $attributes;

    /**
     * @param iterable<IdentityAttribute> $attributes
     */
    public function __construct(iterable $attributes = [])
    {
        $unique = [];
        foreach ($attributes as $attribute) {
            if (!in_array($attribute, $unique, true)) {
                $unique[] = $attribute;
            }
        }

        if (
            count($unique) > 1
            && !(
                in_array(IdentityAttribute::DOUBTFUL, $unique, true)
                && in_array(IdentityAttribute::HOMONYM, $unique, true)
                && count($unique) === 2
            )
        ) {
            throw new InvalidValueObject(
                'Only the « douteux » and « homonyme » identity attributes may be combined ([EXI ID 24]).'
            );
        }

        $this->attributes = $unique;
    }

    public static function empty(): self
    {
        return new self();
    }

    public function isEmpty(): bool
    {
        return $this->attributes === [];
    }

    public function has(IdentityAttribute $attribute): bool
    {
        return in_array($attribute, $this->attributes, true);
    }

    /**
     * Whether the identity must be held at the PROVISIONAL status and its INS
     * invalidated, per [EXI ID 26].
     */
    public function requiresProvisionalStatus(): bool
    {
        return $this->has(IdentityAttribute::DOUBTFUL)
            || $this->has(IdentityAttribute::FICTITIOUS);
    }

    /**
     * Whether calling the INSi téléservice is forbidden for this identity,
     * per [EXI ID 26].
     */
    public function blocksInsiLookup(): bool
    {
        return $this->requiresProvisionalStatus();
    }

    public function equals(self $other): bool
    {
        if (count($this->attributes) !== count($other->attributes)) {
            return false;
        }

        foreach ($this->attributes as $attribute) {
            if (!$other->has($attribute)) {
                return false;
            }
        }

        return true;
    }
}
