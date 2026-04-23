<?php

namespace App\Enum;

enum BookingStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::PAID => 'Payee',
            self::CANCELLED => 'Annulee',
        };
    }
}
