<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\ValueObject\HistoricalInsIdentifier;
use Healthcare\Identity\ValueObject\InsAssigningAuthority;
use Healthcare\Identity\ValueObject\InsIdentifier;
use Healthcare\Identity\ValueObject\InsIdentifierHistory;
use Healthcare\Identity\ValueObject\InsMatricule;
use Healthcare\Kernel\ValueObject\Date;
use PHPUnit\Framework\TestCase;

final class InsIdentifierHistoryTest extends TestCase
{
    /** @var list<HistoricalInsIdentifier> */
    private array $entries;

    protected function setUp(): void
    {
        $this->entries = [
            new HistoricalInsIdentifier(
                new InsIdentifier(new InsMatricule('185057512345673'), InsAssigningAuthority::nir()),
                new Date('2015-01-01'),
                new Date('2020-06-30'),
            ),
            new HistoricalInsIdentifier(
                new InsIdentifier(new InsMatricule('885997512345663'), InsAssigningAuthority::nia()),
            ),
        ];
    }

    public function testEmptyHistory(): void
    {
        $history = InsIdentifierHistory::empty();

        self::assertTrue($history->isEmpty());
        self::assertSame(0, $history->count());
    }

    public function testKeepsEntriesInGivenOrder(): void
    {
        $history = new InsIdentifierHistory($this->entries);

        self::assertSame(2, $history->count());
        self::assertTrue($history->entries[0]->identifier->matricule->equals(new InsMatricule('185057512345673')));
    }

    public function testEqualityIsOrderIndependent(): void
    {
        $a = new InsIdentifierHistory($this->entries);
        $b = new InsIdentifierHistory(array_reverse($this->entries));

        self::assertTrue($a->equals($b));
    }

    public function testEqualityRequiresSameEntries(): void
    {
        $a = new InsIdentifierHistory($this->entries);
        $b = new InsIdentifierHistory([$this->entries[0]]);

        self::assertFalse($a->equals($b));
    }
}
