<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * A concept that may be expressed by several codings and an optional
 * plain-text representation (FHIR CodeableConcept semantics).
 *
 * Typical use: an analyte carrying both a local laboratory code and its
 * LOINC mapping simultaneously.
 *
 * Invariant: at least one coding OR a non-blank text. Codings are
 * deduplicated by strict equality, which keeps order-independent
 * equals() sound.
 */
final readonly class CodeableConcept
{
    /** @var list<Coding> */
    public array $codings;

    public ?string $text;

    /**
     * @param list<Coding> $codings
     */
    public function __construct(array $codings, ?string $text = null)
    {
        $normalizedText = $text === null ? null : trim($text);

        $unique = [];
        foreach ($codings as $coding) {
            foreach ($unique as $existing) {
                if ($existing->equals($coding)) {
                    continue 2;
                }
            }
            $unique[] = $coding;
        }

        if ($unique === [] && ($normalizedText === null || $normalizedText === '')) {
            throw new InvalidValueObject('A codeable concept requires at least one coding or a text.');
        }

        $this->codings = $unique;
        $this->text = $normalizedText === '' ? null : $normalizedText;
    }

    public function equals(self $other): bool
    {
        if ($this->text !== $other->text || count($this->codings) !== count($other->codings)) {
            return false;
        }

        foreach ($this->codings as $coding) {
            $matched = false;
            foreach ($other->codings as $otherCoding) {
                if ($coding->equals($otherCoding)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    public function hasCoding(Coding $coding): bool
    {
        foreach ($this->codings as $existing) {
            if ($existing->equals($coding)) {
                return true;
            }
        }

        return false;
    }

    public function hasCodingIn(CodeSystem $system, string $code): bool
    {
        foreach ($this->codings as $existing) {
            if ($existing->system->equals($system) && $existing->code === $code) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        if ($this->text !== null) {
            return $this->text;
        }

        return (string) $this->codings[0];
    }
}
