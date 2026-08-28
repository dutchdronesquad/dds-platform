<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

final class UtcDateTime
{
    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function valuesForStorage(array $values): array
    {
        return array_map(function (mixed $value): mixed {
            if (! is_string($value) || trim($value) === '') {
                return $value;
            }

            try {
                return CarbonImmutable::parse($value)
                    ->utc()
                    ->toIso8601String();
            } catch (InvalidFormatException) {
                return $value;
            }
        }, $values);
    }
}
