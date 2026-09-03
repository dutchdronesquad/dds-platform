<?php

namespace App\Http\Requests\Admin;

use App\Enums\LocationEnvironment;
use App\Models\Location;
use App\Models\MediaAsset;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Location::class) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $location = $this->location();

        return [
            'cover_image_id' => [
                'nullable',
                'integer',
                Rule::exists(MediaAsset::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($location): void {
                    $mediaAsset = MediaAsset::query()
                        ->with('media')
                        ->find($value);

                    if (! $mediaAsset instanceof MediaAsset || ! $mediaAsset->isImage()) {
                        $fail('De geselecteerde omslagafbeelding is niet geldig.');

                        return;
                    }

                    if (
                        $mediaAsset->archived_at !== null
                        && $location?->cover_image_id !== $mediaAsset->id
                    ) {
                        $fail('Een gearchiveerde afbeelding kan niet als nieuwe omslag worden gekozen.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique(Location::class, 'slug')->ignore($location),
            ],
            'description' => ['required', 'array'],
            'description.en' => ['nullable', 'string', 'max:5000'],
            'description.nl' => ['required', 'string', 'max:5000'],
            'street' => ['required', 'string', 'max:255'],
            'house_number' => ['required', 'string', 'max:20'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'environment' => ['required', Rule::enum(LocationEnvironment::class)],
            'floor_size_square_metres' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ceiling_height_metres' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999.99'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['string', 'max:100'],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'cover_image_id' => 'omslagafbeelding',
            'name' => 'naam',
            'slug' => 'URL-slug',
            'description' => 'omschrijving',
            'description.en' => 'Engelse omschrijving',
            'description.nl' => 'omschrijving',
            'street' => 'straat',
            'house_number' => 'huisnummer',
            'postal_code' => 'postcode',
            'city' => 'plaats',
            'country_code' => 'landcode',
            'environment' => 'omgeving',
            'floor_size_square_metres' => 'vloeroppervlak',
            'ceiling_height_metres' => 'plafondhoogte',
            'facilities' => 'faciliteiten',
            'website_url' => 'website',
            'latitude' => 'breedtegraad',
            'longitude' => 'lengtegraad',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->string('slug')->trim()->isEmpty() && $this->filled('name')) {
            $this->merge(['slug' => Str::slug($this->string('name')->toString())]);
        }
    }

    protected function location(): ?Location
    {
        $location = $this->route('location');

        return $location instanceof Location ? $location : null;
    }
}
