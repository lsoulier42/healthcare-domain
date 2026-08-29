<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\IdentityStatus;
use Healthcare\Identity\ValueObject\InsAssigningAuthority;
use Healthcare\Identity\ValueObject\InsIdentifier;
use Healthcare\Identity\ValueObject\InsMatricule;
use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Identity\ValueObject\StrictIdentityTraits;
use Healthcare\Kernel\ValueObject\Date;
use PHPUnit\Framework\TestCase;

final class PatientIdentityTest extends TestCase
{
    private function traits(): StrictIdentityTraits
    {
        return new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: 'Ada',
            birthGivenNames: ['Ada'],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        );
    }

    private function ins(): InsIdentifier
    {
        return new InsIdentifier(
            new InsMatricule('185057512345673'),
            InsAssigningAuthority::nir(),
        );
    }

    public function testProvisionalHasNoIns(): void
    {
        $identity = PatientIdentity::provisional($this->traits());

        self::assertSame(IdentityStatus::PROVISIONAL, $identity->status);
        self::assertNull($identity->insIdentifier);
    }

    public function testValidatedHasNoIns(): void
    {
        $identity = PatientIdentity::validated($this->traits());

        self::assertSame(IdentityStatus::VALIDATED, $identity->status);
        self::assertNull($identity->insIdentifier);
    }

    public function testRecoveredRequiresIns(): void
    {
        $identity = PatientIdentity::recovered($this->traits(), $this->ins());

        self::assertSame(IdentityStatus::RECOVERED, $identity->status);
        self::assertTrue($identity->insIdentifier?->equals($this->ins()));
    }

    public function testQualifiedRequiresIns(): void
    {
        $identity = PatientIdentity::qualified($this->traits(), $this->ins());

        self::assertSame(IdentityStatus::QUALIFIED, $identity->status);
        self::assertTrue($identity->insIdentifier?->equals($this->ins()));
    }

    public function testRecoveredAndQualifiedWithNiaAuthority(): void
    {
        $nia = new InsIdentifier(
            new InsMatricule('885997512345663'),
            InsAssigningAuthority::nia(),
        );

        self::assertSame(IdentityStatus::RECOVERED, PatientIdentity::recovered($this->traits(), $nia)->status);
        self::assertSame(IdentityStatus::QUALIFIED, PatientIdentity::qualified($this->traits(), $nia)->status);
    }

    public function testValueEquality(): void
    {
        self::assertTrue(
            PatientIdentity::provisional($this->traits())->equals(PatientIdentity::provisional($this->traits())),
        );
        self::assertTrue(PatientIdentity::qualified($this->traits(), $this->ins())->equals(
            PatientIdentity::qualified($this->traits(), $this->ins()),
        ));

        self::assertFalse(
            PatientIdentity::provisional($this->traits())->equals(PatientIdentity::validated($this->traits())),
        );
        self::assertFalse(PatientIdentity::recovered($this->traits(), $this->ins())->equals(
            PatientIdentity::qualified($this->traits(), $this->ins()),
        ));
    }
}
