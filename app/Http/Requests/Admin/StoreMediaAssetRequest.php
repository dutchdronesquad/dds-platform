<?php

namespace App\Http\Requests\Admin;

use App\Models\MediaAsset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class StoreMediaAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MediaAsset::class) === true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $supportedLocales = array_keys(config('localization.supported_locales'));

        return [
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'])
                    ->max((int) ceil(((int) config('media-library.max_file_size')) / 1024)),
            ],
            'alt_text' => ['nullable', 'array:'.implode(',', $supportedLocales)],
            'alt_text.*' => ['nullable', 'string', 'max:500'],
            'return_to' => ['nullable', 'string', 'in:library'],
        ];
    }

    /** @return array<string, string>|null */
    public function altText(): ?array
    {
        $validated = $this->validated();
        $values = $validated['alt_text'] ?? [];

        if (! is_array($values)) {
            return null;
        }

        $altText = [];

        foreach ($values as $locale => $value) {
            if (! is_string($locale) || ! is_string($value)) {
                continue;
            }

            $normalizedValue = Str::squish($value);

            if ($normalizedValue !== '') {
                $altText[$locale] = $normalizedValue;
            }
        }

        return $altText === [] ? null : $altText;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'file' => 'bestand',
            'alt_text' => 'alternatieve tekst',
        ];
    }
}
