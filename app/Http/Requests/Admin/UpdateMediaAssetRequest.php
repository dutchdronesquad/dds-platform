<?php

namespace App\Http\Requests\Admin;

use App\Models\MediaAsset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateMediaAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mediaAsset = $this->route('mediaAsset');

        return $mediaAsset instanceof MediaAsset
            && $this->user()?->can('update', $mediaAsset) === true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $supportedLocales = array_keys(config('localization.supported_locales'));

        return [
            'alt_text' => ['nullable', 'array:'.implode(',', $supportedLocales)],
            'alt_text.*' => ['nullable', 'string', 'max:500'],
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
}
