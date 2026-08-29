<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

enum ContactPointType: string
{
    case EMAIL = 'email';
    case PHONE = 'phone';
}
