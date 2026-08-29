<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

/**
 * Neutral document content abstraction: text, binary media reference,
 * or an external URI. No filesystem/storage dependency.
 */
final readonly class DocumentContent
{
    public function __construct(
        public ?string $text = null,
        public ?string $mediaReference = null,
        public ?string $externalUri = null,
    ) {
    }

    public function equals(self $other): bool
    {
        return $this->text === $other->text
            && $this->mediaReference === $other->mediaReference
            && $this->externalUri === $other->externalUri;
    }
}
