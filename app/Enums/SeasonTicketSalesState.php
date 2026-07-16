<?php

namespace App\Enums;

enum SeasonTicketSalesState: string
{
    case NotOffered = 'not_offered';
    case ComingSoon = 'coming_soon';
    case Available = 'available';
    case SoldOut = 'sold_out';
    case Closed = 'closed';
}
