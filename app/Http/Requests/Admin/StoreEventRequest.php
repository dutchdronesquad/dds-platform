<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Season;
use App\Rules\RegistrationUrl;
use App\Support\LocalDateTime;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Event::class) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $event = $this->event();

        return [
            'location_id' => ['required', 'integer', Rule::exists(Location::class, 'id')],
            'season_id' => ['nullable', 'integer', Rule::exists(Season::class, 'id')],
            'cover_image_id' => [
                'nullable',
                'integer',
                Rule::exists(MediaAsset::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($event): void {
                    $mediaAsset = MediaAsset::query()
                        ->with('media')
                        ->find($value);

                    if (! $mediaAsset instanceof MediaAsset || ! $mediaAsset->isImage()) {
                        $fail('De geselecteerde omslagafbeelding is niet geldig.');

                        return;
                    }

                    if (
                        $mediaAsset->archived_at !== null
                        && $event?->cover_image_id !== $mediaAsset->id
                    ) {
                        $fail('Een gearchiveerde afbeelding kan niet als nieuwe omslag worden gekozen.');
                    }
                },
            ],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:50000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'type' => ['required', Rule::enum(EventType::class)],
            'price_euros' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:42949672.95'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'registration_opens_at' => [
                'nullable',
                'date',
                Rule::when(
                    $this->filled('registration_deadline_at'),
                    ['before_or_equal:registration_deadline_at'],
                ),
            ],
            'registration_deadline_at' => ['nullable', 'date', 'before_or_equal:starts_at'],
            'registration_status' => ['required', Rule::enum(EventRegistrationStatus::class)],
            'registration_url' => [
                'nullable',
                Rule::requiredIf(in_array($this->input('registration_status'), [
                    EventRegistrationStatus::Open->value,
                    EventRegistrationStatus::Waitlist->value,
                ], true)),
                new RegistrationUrl,
                'max:2048',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function eventData(): array
    {
        $validated = $this->validated();
        $price = Arr::pull($validated, 'price_euros');
        $event = $this->event();
        $slug = $event instanceof Event
            ? $event->slug
            : $this->uniqueSlug(
                $validated['title'],
                CarbonImmutable::parse($validated['starts_at']),
            );

        return [
            ...$validated,
            'slug' => $slug,
            'price_cents' => $price === null ? null : (int) round((float) $price * 100),
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'location_id' => 'locatie',
            'season_id' => 'seizoen',
            'cover_image_id' => 'omslagafbeelding',
            'title' => 'titel',
            'content' => 'omschrijving',
            'starts_at' => 'startdatum',
            'ends_at' => 'einddatum',
            'type' => 'eventtype',
            'price_euros' => 'prijs',
            'capacity' => 'deelnemerslimiet',
            'registration_opens_at' => 'start inschrijving',
            'registration_deadline_at' => 'inschrijfdeadline',
            'registration_status' => 'inschrijfstatus',
            'registration_url' => 'inschrijflink',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(LocalDateTime::valuesInUtc([
            'starts_at' => $this->input('starts_at'),
            'ends_at' => $this->input('ends_at'),
            'registration_opens_at' => $this->input('registration_opens_at'),
            'registration_deadline_at' => $this->input('registration_deadline_at'),
        ]));
    }

    protected function event(): ?Event
    {
        $event = $this->route('event');

        return $event instanceof Event ? $event : null;
    }

    private function uniqueSlug(string $title, CarbonImmutable $startsAt): string
    {
        $titleSlug = Str::slug($title) ?: 'event';
        $dateSuffix = '-'.$startsAt->format('Y-m-d');
        $sequence = 1;

        do {
            $sequenceSuffix = $sequence === 1 ? '' : '-'.$sequence;
            $slug = Str::limit(
                $titleSlug,
                255 - Str::length($dateSuffix) - Str::length($sequenceSuffix),
                '',
            ).$dateSuffix.$sequenceSuffix;
            $sequence++;
        } while (Event::query()->where('slug', $slug)->exists());

        return $slug;
    }
}
