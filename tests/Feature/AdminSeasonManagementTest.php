<?php

use App\Enums\Role;
use App\Enums\SeasonTicketSalesState;
use App\Models\Event;
use App\Models\Season;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('only admins can manage seasons', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->get(route('admin.seasons.index'))->assertRedirect(route('login'));

    $this->actingAs($editor)
        ->get(route('admin.seasons.index'))
        ->assertForbidden();

    $this->actingAs($editor)
        ->post(route('admin.seasons.store'), validSeasonPayload())
        ->assertForbidden();
});

test('admins can review season and ticket summaries', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $season = Season::factory()->withTicketOffer()->create(['name' => 'Racejaar 2027']);
    Event::factory()->count(2)->create(['season_id' => $season->id]);

    $this->actingAs($admin)
        ->get(route('admin.seasons.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/seasons/index')
            ->where('summary.total', 1)
            ->where('summary.withTickets', 1)
            ->where('summary.events', 2)
            ->has('seasons.data', 1)
            ->where('seasons.data.0.name', 'Racejaar 2027')
            ->where('seasons.data.0.eventCount', 2)
            ->has('seasons.data.0.ticket'),
        );
});

test('admins can create seasons with an optional ticket price and limit', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->post(route('admin.seasons.store'), validSeasonPayload([
            'name' => 'Winter 2027',
            'slug' => '',
            'ticket_price_euros' => '149.95',
            'ticket_capacity' => 40,
        ]))
        ->assertRedirect();

    $season = Season::query()->where('slug', 'winter-2027')->firstOrFail();
    $ticket = $season->seasonTicket()->firstOrFail();

    expect($season->name)->toBe('Winter 2027')
        ->and($ticket->sales_state)->toBe(SeasonTicketSalesState::Available)
        ->and($ticket->price_cents)->toBe(14995)
        ->and($ticket->capacity)->toBe(40)
        ->and($ticket->registration_url)->toBe('https://example.com/seizoensticket');
});

test('admins can create a season without offering a ticket', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->post(route('admin.seasons.store'), [
            'name' => 'Los seizoen',
            'slug' => 'los-seizoen',
        ])
        ->assertRedirect();

    $season = Season::query()->where('slug', 'los-seizoen')->firstOrFail();

    expect($season->seasonTicket()->exists())->toBeFalse();
});

test('admins can update seasons and stop offering their ticket', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $season = Season::factory()->withTicketOffer()->create();

    $this->actingAs($admin)
        ->put(route('admin.seasons.update', $season), [
            'name' => 'Zomerseizoen 2028',
            'slug' => 'zomerseizoen-2028',
            'ticket_offered' => false,
        ])
        ->assertRedirect(route('admin.seasons.edit', 'zomerseizoen-2028'));

    expect($season->refresh())
        ->name->toBe('Zomerseizoen 2028')
        ->slug->toBe('zomerseizoen-2028')
        ->and($season->seasonTicket()->exists())->toBeFalse();
});

test('season ticket validation rejects invalid sales windows and values', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)
        ->post(route('admin.seasons.store'), validSeasonPayload([
            'ticket_sales_state' => SeasonTicketSalesState::NotOffered->value,
            'ticket_price_euros' => '-1',
            'ticket_capacity' => 0,
            'ticket_registration_url' => null,
            'ticket_sales_opens_at' => '2026-10-02T10:00',
            'ticket_sales_closes_at' => '2026-10-01T10:00',
        ]))
        ->assertSessionHasErrors([
            'ticket_sales_state',
            'ticket_price_euros',
            'ticket_capacity',
            'ticket_sales_opens_at',
        ]);

    $this->actingAs($admin)
        ->post(route('admin.seasons.store'), validSeasonPayload([
            'ticket_registration_url' => null,
        ]))
        ->assertSessionHasErrors('ticket_registration_url');
});

test('seasons with events are protected from deletion', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $season = Season::factory()->create();
    Event::factory()->create(['season_id' => $season->id]);

    $this->actingAs($admin)
        ->delete(route('admin.seasons.destroy', $season))
        ->assertRedirect();

    $this->assertModelExists($season);
});

test('admins can delete an unused season and its ticket', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $season = Season::factory()->withTicketOffer()->create();
    $ticket = $season->seasonTicket()->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('admin.seasons.destroy', $season))
        ->assertRedirect(route('admin.seasons.index'));

    $this->assertModelMissing($season);
    $this->assertModelMissing($ticket);
});

/** @param array<string, mixed> $overrides */
function validSeasonPayload(array $overrides = []): array
{
    return [
        'name' => 'Seizoen 2027',
        'slug' => 'seizoen-2027',
        'ticket_offered' => true,
        'ticket_sales_state' => SeasonTicketSalesState::Available->value,
        'ticket_price_euros' => '125.00',
        'ticket_capacity' => 50,
        'ticket_sales_opens_at' => '2026-09-01T10:00',
        'ticket_sales_closes_at' => '2026-12-01T10:00',
        'ticket_registration_url' => 'https://example.com/seizoensticket',
        'ticket_copy' => 'Toegang tot alle gekoppelde events.',
        ...$overrides,
    ];
}
