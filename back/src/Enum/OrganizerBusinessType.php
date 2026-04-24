<?php

namespace App\Enum;

enum OrganizerBusinessType: string
{
    case INDIVIDUAL = 'INDIVIDUAL';
    case COMPANY = 'COMPANY';
    case ASSOCIATION = 'ASSOCIATION';
    case COLLECTIVE = 'COLLECTIVE';
    case OTHER = 'OTHER';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Independant',
            self::COMPANY => 'Entreprise',
            self::ASSOCIATION => 'Association',
            self::COLLECTIVE => 'Collectif',
            self::OTHER => 'Autre',
        };
    }
}
