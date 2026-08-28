<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use LogicException;

final class LocalDateTime
{
    private const string FormFormat = 'Y-m-d\TH:i';

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function valuesInUtc(array $values): array
    {
        return array_map(function (mixed $value): mixed {
            if (! is_string($value) || trim($value) === '') {
                return $value;
            }

            try {
                return CarbonImmutable::parse($value, self::timezone())
                    ->utc()
                    ->toIso8601String();
            } catch (InvalidFormatException) {
                return $value;
            }
        }, $values);
    }

    public static function forForm(?CarbonInterface $dateTime): ?string
    {
        return $dateTime
            ?->setTimezone(self::timezone())
            ->format(self::FormFormat);
    }

    private static function timezone(): string
    {
        $timezone = config('app.local_timezone');

        if (! is_string($timezone) || $timezone === '') {
            throw new LogicException('The application local timezone must be configured.');
        }

        return $timezone;
    }
}
