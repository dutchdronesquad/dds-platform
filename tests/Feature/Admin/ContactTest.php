<?php

use App\Enums\ContactDeliveryStatus;
use App\Enums\ContactTopic;
use App\Enums\Role;
use App\Models\ContactSubmission;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected from contact management', function () {
    $this->get(route('admin.contact.index'))
        ->assertRedirect(route('login'));
});

test('editors cannot review contact submissions', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->get(route('admin.contact.index'))
        ->assertForbidden();
});

test('admins can review stored contact submissions and delivery follow-ups', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    ContactSubmission::factory()->create([
        'name' => 'Grace Hopper',
        'email' => 'grace@example.test',
        'topic' => ContactTopic::Events,
        'message' => 'Ik wil graag weten wanneer de volgende beginnerstraining plaatsvindt.',
    ]);
    ContactSubmission::factory()->followUpNeeded()->create([
        'name' => 'Katherine Johnson',
    ]);
    ContactSubmission::factory()->failed()->create([
        'name' => 'Margaret Hamilton',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.contact.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/contact/index')
            ->where('summary.total', 3)
            ->where('summary.delivered', 1)
            ->where('summary.followUpNeeded', 2)
            ->where('contactSubmissions.total', 3)
            ->has('contactSubmissions.data', 3)
            ->where('contactSubmissions.data.2.name', 'Grace Hopper')
            ->where('contactSubmissions.data.2.email', 'grace@example.test')
            ->where('contactSubmissions.data.2.topicLabel', ContactTopic::Events->label())
            ->where('contactSubmissions.data.2.deliveryStatus', ContactDeliveryStatus::Sent->value)
        );
});

test('admins can search contact submissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    ContactSubmission::factory()->create(['name' => 'Grace Hopper']);
    ContactSubmission::factory()->create(['name' => 'Ada Lovelace']);

    $this->actingAs($admin)
        ->get(route('admin.contact.index', ['search' => 'GRACE']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'GRACE')
            ->where('contactSubmissions.total', 1)
            ->where('contactSubmissions.data.0.name', 'Grace Hopper')
        );
});

test('admins can open the complete contact submission detail', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $contactSubmission = ContactSubmission::factory()->failed()->create([
        'name' => 'Grace Hopper',
        'email' => 'grace@example.test',
        'topic' => ContactTopic::Partnerships,
        'message' => 'Kunnen we samen een community-evenement voor jonge makers organiseren?',
        'source_context' => 'partners-page',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.contact.show', $contactSubmission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/contact/show')
            ->where('contactSubmission.id', $contactSubmission->id)
            ->where('contactSubmission.name', 'Grace Hopper')
            ->where('contactSubmission.email', 'grace@example.test')
            ->where('contactSubmission.topicLabel', ContactTopic::Partnerships->label())
            ->where('contactSubmission.message', 'Kunnen we samen een community-evenement voor jonge makers organiseren?')
            ->where('contactSubmission.sourceContext', 'partners-page')
            ->where('contactSubmission.deliveryStatus', ContactDeliveryStatus::Failed->value)
            ->has('contactSubmission.deliveryError')
            ->has('contactSubmission.consentedAt')
            ->has('contactSubmission.createdAt')
        );
});
