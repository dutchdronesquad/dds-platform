<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Location;
use App\Models\Season;
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
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique(Event::class, 'slug')->ignore($event),
            ],
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
                'url:http,https',
                'max:2048',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function eventData(): array
    {
        $validated = $this->validated();
        $price = Arr::pull($validated, 'price_euros');

        return [
            ...$validated,
            'price_cents' => $price === null ? null : (int) round((float) $price * 100),
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'location_id' => 'locatie',
            'season_id' => 'seizoen',
            'title' => 'titel',
            'slug' => 'URL-slug',
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
        if ($this->string('slug')->trim()->isEmpty() && $this->filled('title')) {
            $this->merge(['slug' => Str::slug($this->string('title')->toString())]);
        }
    }

    protected function event(): ?Event
    {
        $event = $this->route('event');

        return $event instanceof Event ? $event : null;
    }
}
