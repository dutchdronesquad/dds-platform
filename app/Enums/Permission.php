<?php

namespace App\Enums;

enum Permission: string
{
    case ViewEvents = 'events.view';
    case CreateEvents = 'events.create';
    case UpdateEvents = 'events.update';
    case DeleteEvents = 'events.delete';

    case ViewRedirects = 'redirects.view';

    case ViewUsers = 'users.view';
    case UpdateUsers = 'users.update';
}
