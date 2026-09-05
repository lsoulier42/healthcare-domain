<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\Service\FirstGivenNameConsistency;
use PHPUnit\Framework\TestCase;

final class FirstGivenNameConsistencyTest extends TestCase
{
    public function testFirstGivenNameIsConsistentWithTheStartOfTheList(): void
    {
        // Guide d'implémentation INS v3.0, [EXI ID 10] examples: the list is
        // « JEAN CHRISTOPHE PIERRE ».
        $consistentFirstGivenNames = [
            'JEAN',
            'JEAN CHRISTOPHE',
            'JEAN-CHRISTOPHE',
            'JEAN-CHRISTOPHE-PIERRE',
            'JEAN CHRISTOPHE-PIERRE',
        ];
        foreach ($consistentFirstGivenNames as $first) {
            self::assertTrue(
                FirstGivenNameConsistency::isConsistent($first, ['JEAN', 'CHRISTOPHE', 'PIERRE']),
                $first,
            );
        }
    }

    public function testNonPrefixGivenNameIsInconsistent(): void
    {
        self::assertFalse(
            FirstGivenNameConsistency::isConsistent('CHRISTOPHE', ['JEAN', 'CHRISTOPHE', 'PIERRE']),
        );
        self::assertFalse(
            FirstGivenNameConsistency::isConsistent('PIERRE', ['JEAN', 'CHRISTOPHE', 'PIERRE']),
        );
    }

    public function testSeparatorsAreEquivalentToSpaces(): void
    {
        self::assertTrue(
            FirstGivenNameConsistency::isConsistent("JEAN'CLAUDE", ['JEAN', 'CLAUDE']),
        );
        self::assertTrue(
            FirstGivenNameConsistency::isConsistent('jean', ['JEAN', 'CHRISTOPHE']),
        );
    }

    public function testSingleGivenNameList(): void
    {
        self::assertTrue(FirstGivenNameConsistency::isConsistent('ADA', ['ADA']));
        self::assertFalse(FirstGivenNameConsistency::isConsistent('ADA LOUISE', ['ADA']));
    }

    public function testEmptyValuesAreInconsistent(): void
    {
        self::assertFalse(FirstGivenNameConsistency::isConsistent('', ['JEAN']));
        self::assertFalse(FirstGivenNameConsistency::isConsistent('JEAN', []));
        self::assertFalse(FirstGivenNameConsistency::isConsistent('   ', ['JEAN']));
    }
}
