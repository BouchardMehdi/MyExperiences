<?php

namespace App\Enum;

enum ExperienceStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PUBLISHED => 'Publiee',
            self::ARCHIVED => 'Archivee',
        };
    }
}
