<?php

declare(strict_types=1);

namespace Healthcare\Tests\Medication;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Atc;
use Healthcare\Kernel\ValueObject\Cip13;
use Healthcare\Kernel\ValueObject\Cip7;
use Healthcare\Kernel\ValueObject\Cis;
use Healthcare\Kernel\ValueObject\Quantity;
use Healthcare\Kernel\ValueObject\QuantityComparator;
use Healthcare\Kernel\ValueObject\Ratio;
use Healthcare\Kernel\ValueObject\Unit;
use Healthcare\Kernel\ValueObject\Ucd;
use Healthcare\Medication\Entity\ActiveSubstance;
use Healthcare\Medication\Entity\Medication;
use Healthcare\Medication\ValueObject\MedicationComponent;
use Healthcare\Medication\Entity\MedicationPresentation;
use Healthcare\Medication\ValueObject\AdministrationRouteCode;
use Healthcare\Medication\ValueObject\MedicationStrength;
use Healthcare\Medication\ValueObject\PharmaceuticalFormCode;
use PHPUnit\Framework\TestCase;

final class MedicationTest extends TestCase
{
    public function testStrengthCanBeASimpleQuantity(): void
    {
        $substance = new ActiveSubstance('as-1', 'Paracetamol');
        $component = new MedicationComponent(
            $substance,
            new MedicationStrength(new Quantity('500', Unit::milligram())),
        );

        self::assertNotNull($component->strength);
        self::assertSame('500 mg', (string) $component->strength);
    }

    public function testStrengthCanBeAConcentrationRatio(): void
    {
        $component = new MedicationComponent(
            new ActiveSubstance('as-1', 'Morphine'),
            new MedicationStrength(new Ratio(
                new Quantity('1', Unit::milligram()),
                new Quantity('1', Unit::milliliter()),
            )),
        );

        self::assertSame('1 mg / 1 mL', (string) $component->strength);
    }

    public function testStrengthRejectsZeroAndNegativeQuantities(): void
    {
        $this->expectException(InvalidValueObject::class);
        new MedicationStrength(new Quantity('0', Unit::milligram()));
    }

    public function testStrengthRejectsZeroInScientificNotation(): void
    {
        $this->expectException(InvalidValueObject::class);
        new MedicationStrength(new Quantity('0e3', Unit::milligram()));
    }

    public function testStrengthRejectsNegativeZero(): void
    {
        $this->expectException(InvalidValueObject::class);
        new MedicationStrength(new Quantity('-0.0', Unit::milligram()));
    }

    public function testStrengthAcceptsPositiveScientificNotation(): void
    {
        $strength = new MedicationStrength(new Quantity('5e2', Unit::milligram()));

        self::assertSame('5e2 mg', (string) $strength);
    }

    public function testStrengthRejectsComparisonModifiers(): void
    {
        $this->expectException(InvalidValueObject::class);
        new MedicationStrength(new Quantity('500', Unit::milligram(), QuantityComparator::LESS_THAN));
    }

    public function testStrengthRejectsComparisonModifiersInRatioParts(): void
    {
        $this->expectException(InvalidValueObject::class);
        new MedicationStrength(new Ratio(
            new Quantity('1', Unit::milligram(), QuantityComparator::LESS_THAN),
            new Quantity('1', Unit::milliliter()),
        ));
    }

    public function testStrengthRejectsNonPositiveRatioParts(): void
    {
        $this->expectException(InvalidValueObject::class);
        new MedicationStrength(new Ratio(
            new Quantity('-1', Unit::milligram()),
            new Quantity('1', Unit::milliliter()),
        ));
    }

    public function testComponentsAreDeduplicatedByValue(): void
    {
        $medication = new Medication('med-1', 'Produit exemple');
        $substanceA = new ActiveSubstance('as-1', 'Paracetamol');
        $substanceB = new ActiveSubstance('as-1', 'Paracetamol');
        $strength = new MedicationStrength(new Quantity('500', Unit::milligram()));

        $medication->addComponent(new MedicationComponent($substanceA, $strength));
        $medication->addComponent(new MedicationComponent($substanceB, $strength));

        self::assertCount(1, $medication->components());
    }

    public function testComponentsWithDifferentSubstancesCoexist(): void
    {
        $medication = new Medication('med-1', 'Produit exemple');

        $medication->addComponent(new MedicationComponent(new ActiveSubstance('as-1', 'Paracetamol')));
        $medication->addComponent(new MedicationComponent(new ActiveSubstance('as-2', 'Codeine')));

        self::assertCount(2, $medication->components());
    }

    /**
     * Source: arrêté du 30/12/2021 — l'UCD est lié à la présentation
     * (un UCD par CIP) ; le CIS (8 chiffres) identifie le produit.
     */
    public function testMedicationIsASpecialtyWithCisAndMultiplePresentations(): void
    {
        $medication = new Medication(
            'med-1',
            'Produit exemple',
            cis: new Cis('60000000'),
            pharmaceuticalForm: PharmaceuticalFormCode::fromEdqm('10219000', 'Tablet'),
        );

        $medication->addAdministrationRoute(AdministrationRouteCode::fromEdqm('20053000', 'Oral use'));

        $presentationA = new MedicationPresentation(
            'pres-1',
            $medication,
            cip13: new Cip13('3400931234560'),
            ucd: new Ucd('1234567'),
            packagingDescription: 'Boîte de 16 comprimés',
        );
        $presentationB = new MedicationPresentation(
            'pres-2',
            $medication,
            cip7: new Cip7('3400934'),
        );

        $medication->addPresentation($presentationA);
        $medication->addPresentation($presentationB);

        $form = $medication->pharmaceuticalForm();

        self::assertSame('60000000', (string) $medication->cis());
        self::assertNotNull($form);
        self::assertSame('10219000', $form->coding->code);
        self::assertSame('20053000', $medication->administrationRoutes()[0]->coding->code);
        self::assertCount(2, $medication->presentations());
        self::assertSame('3400931234560', (string) $presentationA->cip13());
        self::assertSame('1234567', (string) $presentationA->ucd());
        self::assertSame('3400934', (string) $presentationB->cip7());
    }

    public function testMedicationHoldsZeroToManyClassifications(): void
    {
        $medication = new Medication('med-1', 'Produit exemple');
        $medication->addClassification(new Atc('N02BE01'));
        $medication->addClassification(new Atc('N02BE51'));
        $medication->addClassification(new Atc('N02BE01'));

        self::assertCount(2, $medication->classifications());
    }

    public function testPresentationRegistersItselfOnMedication(): void
    {
        $medication = new Medication('med-1', 'Produit exemple');
        new MedicationPresentation('pres-1', $medication);

        self::assertCount(1, $medication->presentations());

        $medication->removePresentation($medication->presentations()[0]);
        self::assertCount(0, $medication->presentations());
    }
}
