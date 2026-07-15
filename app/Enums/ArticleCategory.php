<?php

namespace App\Enums;

enum ArticleCategory: string
{
    case News = 'news';
    case Announcement = 'announcement';
    case Community = 'community';
    case RaceReport = 'race_report';
}
