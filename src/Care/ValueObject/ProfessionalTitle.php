<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

enum ProfessionalTitle: string
{
    case DOCTOR = 'doctor';
    case PROFESSOR = 'professor';

    public function abbreviation(): string
    {
        return match ($this) {
            self::DOCTOR => 'Dr',
            self::PROFESSOR => 'Pr',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DOCTOR => 'Docteur',
            self::PROFESSOR => 'Professeur',
        };
    }
}
