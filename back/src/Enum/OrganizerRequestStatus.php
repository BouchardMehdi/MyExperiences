<?php

namespace App\Enum;

enum OrganizerRequestStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::APPROVED => 'Approuvee',
            self::REJECTED => 'Refusee',
        };
    }
}
