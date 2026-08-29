<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

use Healthcare\Kernel\ValueObject\Coding;

/**
 * Organization category/type coded with an externally governed terminology.
 * No coding system is imposed: consumers may use FINESS categories, JDV
 * value sets, or any other system relevant to their context.
 */
final readonly class OrganizationCategoryCode
{
    public function __construct(public Coding $coding)
    {
    }

    public function equals(self $other): bool
    {
        return $this->coding->equals($other->coding);
    }

    public function sameCodeAs(self $other): bool
    {
        return $this->coding->sameCodeAs($other->coding);
    }

    public function __toString(): string
    {
        return (string) $this->coding;
    }
}
