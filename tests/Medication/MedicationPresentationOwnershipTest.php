<?php

declare(strict_types=1);

namespace Healthcare\Tests\Medication;

use Healthcare\Kernel\Exception\InvalidDomainState;
use Healthcare\Medication\Entity\Medication;
use Healthcare\Medication\Entity\MedicationPresentation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MedicationPresentationOwnershipTest extends TestCase
{
    #[DataProvider('ownerIds')]
    public function testCannotAddAPresentationOwnedByAnotherInstance(string $otherId): void
    {
        $owner = new Medication('medication', 'Owner');
        $other = new Medication($otherId, 'Other');
        $presentation = new MedicationPresentation('presentation', $owner);

        $this->expectException(InvalidDomainState::class);
        try {
            $other->addPresentation($presentation);
        } finally {
            self::assertSame([], $other->presentations());
            self::assertSame([$presentation], $owner->presentations());
            self::assertSame($owner, $presentation->medication());
        }
    }

    public function testConstructorRejectsDuplicateIdsWithoutReplacingTheOriginal(): void
    {
        $owner = new Medication('medication', 'Owner');
        $original = new MedicationPresentation('presentation', $owner);

        $this->expectException(InvalidDomainState::class);
        try {
            new MedicationPresentation('presentation', $owner);
        } finally {
            self::assertSame([$original], $owner->presentations());
        }
    }

    public function testExplicitAdditionRejectsAnotherInstanceWithTheSameId(): void
    {
        $owner = new Medication('medication', 'Owner');
        $original = new MedicationPresentation('presentation', $owner);
        $copy = clone $original;

        $this->expectException(InvalidDomainState::class);
        try {
            $owner->addPresentation($copy);
        } finally {
            self::assertSame([$original], $owner->presentations());
        }
    }

    public function testAddingTheSameInstanceIsIdempotent(): void
    {
        $owner = new Medication('medication', 'Owner');
        $presentation = new MedicationPresentation('presentation', $owner);
        $owner->addPresentation($presentation);
        $owner->addPresentation($presentation);

        self::assertSame([$presentation], $owner->presentations());
    }

    #[DataProvider('ownerIds')]
    public function testRemovalRejectsAForeignOwnerEvenWithAMatchingPresentationId(string $otherId): void
    {
        $owner = new Medication('medication', 'Owner');
        $other = new Medication($otherId, 'Other');
        $original = new MedicationPresentation('presentation', $owner);
        $foreign = new MedicationPresentation('presentation', $other);

        $this->expectException(InvalidDomainState::class);
        try {
            $owner->removePresentation($foreign);
        } finally {
            self::assertSame([$original], $owner->presentations());
            self::assertSame([$foreign], $other->presentations());
        }
    }

    public function testAStaleInstanceCannotRemoveItsReplacement(): void
    {
        $owner = new Medication('medication', 'Owner');
        $original = new MedicationPresentation('presentation', $owner);
        $owner->removePresentation($original);
        $replacement = new MedicationPresentation('presentation', $owner);

        $this->expectException(InvalidDomainState::class);
        try {
            $owner->removePresentation($original);
        } finally {
            self::assertSame([$replacement], $owner->presentations());
        }
    }

    public function testRemovalIsIdempotentAndPreservesTheOwnerAndOtherPresentations(): void
    {
        $owner = new Medication('medication', 'Owner');
        $first = new MedicationPresentation('first', $owner);
        $second = new MedicationPresentation('second', $owner);
        $owner->removePresentation($first);
        $owner->removePresentation($first);

        self::assertSame([$second], $owner->presentations());
        self::assertSame($owner, $first->medication());
        $owner->addPresentation($first);
        self::assertSame([$second, $first], $owner->presentations());
    }

    /** @return iterable<string, array{string}> */
    public static function ownerIds(): iterable
    {
        yield 'different medication' => ['other'];
        yield 'same id but different instance' => ['medication'];
    }
}
