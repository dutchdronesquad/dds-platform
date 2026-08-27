<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

final class RegistrationUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('De :attribute moet een geldige http-, https- of mailto-link zijn.');

            return;
        }

        if (Validator::make(['url' => $value], ['url' => 'url:http,https'])->passes()) {
            return;
        }

        if (! Str::startsWith($value, 'mailto:') || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            $fail('De :attribute moet een geldige http-, https- of mailto-link zijn.');

            return;
        }

        $recipient = rawurldecode(Str::before(Str::after($value, 'mailto:'), '?'));

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            $fail('De :attribute moet een geldig e-mailadres in de mailto-link bevatten.');
        }
    }
}
