<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Beheerder',
            self::Editor => 'Redacteur',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Volledige toegang tot gebruikers en alle beschikbare beheerfuncties.',
            self::Editor => 'Toegang tot toegewezen contentfuncties, zonder gebruikers- of rollenbeheer.',
        };
    }

    /** @return list<Permission> */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Editor => [
                Permission::ViewEvents,
                Permission::CreateEvents,
                Permission::UpdateEvents,
                Permission::ViewMedia,
                Permission::CreateMedia,
                Permission::UpdateMedia,
                Permission::ViewRedirects,
            ],
        };
    }
}
