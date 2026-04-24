<?php

namespace App\Enum;

enum OrganizerRequestScreeningStatus: string
{
    case PRE_VALIDATED = 'PRE_VALIDATED';
    case NEEDS_REVIEW = 'NEEDS_REVIEW';
    case AUTO_REJECTED = 'AUTO_REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::PRE_VALIDATED => 'Pre-validee',
            self::NEEDS_REVIEW => 'A revoir',
            self::AUTO_REJECTED => 'Refusee automatiquement',
        };
    }
}
