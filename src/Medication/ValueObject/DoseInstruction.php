<?php

declare(strict_types=1);

namespace Healthcare\Medication\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Quantity;

/**
 * Generic dosage instruction for a medication line.
 *
 * The instruction is text-first (« 1 comprimé matin et soir pendant 7
 * jours ») and may be complemented by structured fields — quantity per
 * intake, frequency, duration and route — without locking the exchange
 * profile. The French ordonnance simples and e-prescription (SCOR) profiles
 * differ; this value object stays profile-agnostic and the prescription-line
 * aggregate remains an application concern until the target profile is fixed.
 *
 * Factual references: the structured fields reuse the package's existing
 * semantics (Quantity, AdministrationRouteCode/EDQM). The exchange profiles
 * of the ordonnance numérique are documented by the Assurance Maladie
 * (base e-prescription) — out of scope here.
 */
final readonly class DoseInstruction
{
    public string $text;

    public ?string $frequency;

    public ?string $duration;

    public function __construct(
        string $text,
        public ?Quantity $quantity,
        ?string $frequency,
        ?string $duration,
        public ?AdministrationRouteCode $route,
    ) {
        $text = trim($text);
        $frequency = $frequency === null ? null : trim($frequency);
        $duration = $duration === null ? null : trim($duration);

        if ($text === '') {
            throw new InvalidValueObject('A dose instruction requires non-blank text.');
        }

        $this->text = $text;
        $this->frequency = $frequency === '' ? null : $frequency;
        $this->duration = $duration === '' ? null : $duration;
    }

    public static function fromText(string $text): self
    {
        return new self($text, null, null, null, null);
    }

    public static function fromParts(
        string $text,
        ?Quantity $quantity = null,
        ?string $frequency = null,
        ?string $duration = null,
        ?AdministrationRouteCode $route = null,
    ): self {
        return new self($text, $quantity, $frequency, $duration, $route);
    }

    public function equals(self $other): bool
    {
        $sameQuantity = $this->quantity === null
            ? $other->quantity === null
            : $other->quantity !== null && $this->quantity->equals($other->quantity);

        $sameRoute = $this->route === null
            ? $other->route === null
            : $other->route !== null && $this->route->equals($other->route);

        return $this->text === $other->text
            && $sameQuantity
            && $this->frequency === $other->frequency
            && $this->duration === $other->duration
            && $sameRoute;
    }
}
