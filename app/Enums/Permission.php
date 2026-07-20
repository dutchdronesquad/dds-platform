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

    case ViewRoles = 'roles.view';

    public function label(): string
    {
        return match ($this) {
            self::ViewEvents => 'Events bekijken',
            self::CreateEvents => 'Events aanmaken',
            self::UpdateEvents => 'Events bijwerken',
            self::DeleteEvents => 'Events verwijderen',
            self::ViewRedirects => 'Redirects bekijken',
            self::ViewUsers => 'Gebruikers bekijken',
            self::UpdateUsers => 'Gebruikers bijwerken',
            self::ViewRoles => 'Rollen en rechten bekijken',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ViewEvents => 'Geeft toegang tot het eventoverzicht en eventdetails.',
            self::CreateEvents => 'Maakt het aanmaken van nieuwe events mogelijk.',
            self::UpdateEvents => 'Maakt het wijzigen en publiceren van events mogelijk.',
            self::DeleteEvents => 'Maakt het definitief verwijderen van events mogelijk.',
            self::ViewRedirects => 'Geeft toegang tot het overzicht van legacy redirects.',
            self::ViewUsers => 'Geeft toegang tot het gebruikersoverzicht.',
            self::UpdateUsers => 'Maakt het wijzigen van profielen, rollen en accountstatus mogelijk.',
            self::ViewRoles => 'Geeft toegang tot het alleen-lezen rollen- en rechtenoverzicht.',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ViewEvents, self::CreateEvents, self::UpdateEvents, self::DeleteEvents => 'events',
            self::ViewRedirects => 'redirects',
            self::ViewUsers, self::UpdateUsers => 'users',
            self::ViewRoles => 'roles',
        };
    }

    public function groupLabel(): string
    {
        return match ($this) {
            self::ViewEvents, self::CreateEvents, self::UpdateEvents, self::DeleteEvents => 'Events',
            self::ViewRedirects => 'Redirects',
            self::ViewUsers, self::UpdateUsers => 'Gebruikers',
            self::ViewRoles => 'Rollen en rechten',
        };
    }
}
