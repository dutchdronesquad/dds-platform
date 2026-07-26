<?php

namespace App\Enums;

enum GettingStartedEntrySource: string
{
    case Navigation = 'navigation';
    case Homepage = 'homepage';
    case Event = 'event';
    case Location = 'location';
    case Contact = 'contact';
    case Footer = 'footer';
    case Search = 'search';
}
