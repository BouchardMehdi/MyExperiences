<?php

namespace App\Enum;

enum OrganizerEventType: string
{
    case WORKSHOP = 'WORKSHOP';
    case CULTURE = 'CULTURE';
    case FOOD = 'FOOD';
    case SPORT = 'SPORT';
    case WELLNESS = 'WELLNESS';
    case FAMILY = 'FAMILY';
    case NIGHTLIFE = 'NIGHTLIFE';
    case NATURE = 'NATURE';
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
            self::WORKSHOP => 'Atelier',
            self::CULTURE => 'Culture',
            self::FOOD => 'Gastronomie',
            self::SPORT => 'Sport',
            self::WELLNESS => 'Bien-etre',
            self::FAMILY => 'Famille',
            self::NIGHTLIFE => 'Soiree',
            self::NATURE => 'Nature',
            self::OTHER => 'Autre',
        };
    }
}
