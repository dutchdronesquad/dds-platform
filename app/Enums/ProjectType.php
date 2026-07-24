<?php

namespace App\Enums;

enum ProjectType: string
{
    case Application = 'application';
    case RotorHazardPlugin = 'rotorhazard_plugin';
    case RaceTooling = 'race_tooling';
    case LivestreamTool = 'livestream_tool';
    case HardwareBuild = 'hardware_build';
    case Integration = 'integration';
    case CommunityUtility = 'community_utility';
    case OpenSourceContribution = 'open_source_contribution';

    public function label(): string
    {
        return match ($this) {
            self::Application => 'Applicatie',
            self::RotorHazardPlugin => 'RotorHazard-plugin',
            self::RaceTooling => 'Racetooling',
            self::LivestreamTool => 'Livestreamtool',
            self::HardwareBuild => 'Hardwarebouw',
            self::Integration => 'Integratie',
            self::CommunityUtility => 'Communitytool',
            self::OpenSourceContribution => 'Open-sourcebijdrage',
        };
    }
}
