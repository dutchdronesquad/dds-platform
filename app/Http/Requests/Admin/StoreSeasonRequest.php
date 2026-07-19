<?php

namespace App\Http\Requests\Admin;

use App\Enums\SeasonTicketSalesState;
use App\Models\Season;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Season::class) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $season = $this->season();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique(Season::class, 'slug')->ignore($season),
            ],
            'ticket_offered' => ['sometimes', 'boolean'],
            'ticket_sales_state' => [
                'nullable',
                Rule::requiredIf($this->boolean('ticket_offered')),
                Rule::enum(SeasonTicketSalesState::class),
                Rule::notIn([SeasonTicketSalesState::NotOffered->value]),
            ],
            'ticket_price_euros' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:42949672.95'],
            'ticket_capacity' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ticket_sales_opens_at' => [
                'nullable',
                'date',
                Rule::when(
                    $this->filled('ticket_sales_closes_at'),
                    ['before_or_equal:ticket_sales_closes_at'],
                ),
            ],
            'ticket_sales_closes_at' => ['nullable', 'date'],
            'ticket_registration_url' => [
                'nullable',
                Rule::requiredIf(
                    $this->boolean('ticket_offered')
                    && $this->input('ticket_sales_state') === SeasonTicketSalesState::Available->value,
                ),
                'url:http,https',
                'max:2048',
            ],
            'ticket_copy' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array{name: string, slug?: string} */
    public function seasonData(): array
    {
        $validated = $this->validated();
        $seasonData = ['name' => $this->string('name')->toString()];
        $slug = Arr::get($validated, 'slug');

        if (is_string($slug) && $slug !== '') {
            $seasonData['slug'] = $slug;
        }

        return $seasonData;
    }

    /** @return array<string, mixed>|null */
    public function ticketData(): ?array
    {
        if (! $this->boolean('ticket_offered')) {
            return null;
        }

        $validated = $this->validated();
        $price = $validated['ticket_price_euros'] ?? null;

        return [
            'sales_state' => $validated['ticket_sales_state'],
            'price_cents' => $price === null ? null : (int) round((float) $price * 100),
            'capacity' => $validated['ticket_capacity'] ?? null,
            'sales_opens_at' => $validated['ticket_sales_opens_at'] ?? null,
            'sales_closes_at' => $validated['ticket_sales_closes_at'] ?? null,
            'registration_url' => $validated['ticket_registration_url'] ?? null,
            'copy' => $validated['ticket_copy'] ?? null,
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'seizoensnaam',
            'slug' => 'URL-slug',
            'ticket_sales_state' => 'verkoopstatus',
            'ticket_price_euros' => 'ticketprijs',
            'ticket_capacity' => 'ticketlimiet',
            'ticket_sales_opens_at' => 'start ticketverkoop',
            'ticket_sales_closes_at' => 'einde ticketverkoop',
            'ticket_registration_url' => 'ticketlink',
            'ticket_copy' => 'ticketomschrijving',
        ];
    }

    protected function season(): ?Season
    {
        $season = $this->route('season');

        return $season instanceof Season ? $season : null;
    }
}
