<?php

namespace App\Enums;

enum EventRegistrationStatus: string
{
    case Closed = 'closed';
    case Open = 'open';
    case Waitlist = 'waitlist';
    case Full = 'full';
}
