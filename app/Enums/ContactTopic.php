<?php

namespace App\Enums;

enum ContactTopic: string
{
    case Participation = 'participation';
    case Events = 'events';
    case Partnerships = 'partnerships';
    case Projects = 'projects';
    case Press = 'press';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Participation => 'Meedoen met DDS',
            self::Events => 'Events en trainingen',
            self::Partnerships => 'Partnerschap of samenwerking',
            self::Projects => 'Projectvoorstel',
            self::Press => 'Pers en media',
            self::Other => 'Andere vraag',
        };
    }
}
