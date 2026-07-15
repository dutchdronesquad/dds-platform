<?php

namespace App\Enums;

enum EventType: string
{
    case Training = 'training';
    case Race = 'race';
    case Demo = 'demo';
    case Workshop = 'workshop';
    case Other = 'other';
}
