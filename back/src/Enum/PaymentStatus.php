<?php

namespace App\Enum;

enum PaymentStatus: string
{
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => 'Succes',
            self::FAILED => 'Echec',
        };
    }
}
