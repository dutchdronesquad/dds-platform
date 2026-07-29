<?php

use App\Enums\ContactDeliveryStatus;
use App\Enums\ContactTopic;
use App\Enums\Role;
use App\Listeners\MarkContactNotificationAsSent;
use App\Models\ContactSubmission;
use App\Models\User;
use App\Notifications\ContactRequestReceived;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('the contact page exposes the form contract and optional source context', function () {
    $this->get(route('contact', ['source' => 'partners-page']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/contact')
            ->where('page.title', 'Contact')
            ->where('sourceContext', 'partners-page')
            ->has('topics', count(ContactTopic::cases()))
            ->where('topics.0.value', ContactTopic::Participation->value)
        );
});

test('visitors can submit contact requests and trigger a configured notification', function () {
    Notification::fake();
    config(['mail.default' => 'smtp']);
    $administrator = User::factory()->create();
    $administrator->assignRole(Role::Admin->value);
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);
    $inactiveAdministrator = User::factory()->create(['is_active' => false]);
    $inactiveAdministrator->assignRole(Role::Admin->value);

    $this->post(route('contact.store'), validContactSubmission())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('contact'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Bedankt! Je bericht is opgeslagen. We nemen zo snel mogelijk contact met je op.',
        ]);

    $contactSubmission = ContactSubmission::query()->sole();

    expect($contactSubmission)
        ->name->toBe('Ada Lovelace')
        ->email->toBe('ada@example.test')
        ->topic->toBe(ContactTopic::Projects)
        ->message->toBe('Ik wil graag met DDS samenwerken aan een educatief droneproject.')
        ->source_context->toBe('partners-page')
        ->delivery_status->toBe(ContactDeliveryStatus::Pending)
        ->consented_at->not->toBeNull()
        ->delivered_at->toBeNull()
        ->and($contactSubmission->email)->toBeEmail();

    Notification::assertSentTo(
        $administrator,
        ContactRequestReceived::class,
        fn (ContactRequestReceived $notification, array $channels): bool => $channels === ['mail']
            && $notification->contactSubmission->is($contactSubmission),
    );
    Notification::assertNotSentTo($editor, ContactRequestReceived::class);
    Notification::assertNotSentTo(
        $inactiveAdministrator,
        ContactRequestReceived::class,
    );
});

test('an unconfigured mailer records a clear manual follow-up without losing the request', function () {
    Notification::fake();
    $administrator = User::factory()->create();
    $administrator->assignRole(Role::Admin->value);

    $this->post(route('contact.store'), validContactSubmission())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('contact'));

    $contactSubmission = ContactSubmission::query()->sole();

    expect($contactSubmission)
        ->delivery_status->toBe(ContactDeliveryStatus::NotConfigured)
        ->delivery_error->toBe('E-mailnotificaties zijn niet geconfigureerd. Handmatige opvolging is nodig.')
        ->delivered_at->toBeNull();

    Notification::assertNothingSent();
});

test('missing active administrators records a clear manual follow-up', function () {
    Notification::fake();
    config(['mail.default' => 'smtp']);

    $this->post(route('contact.store'), validContactSubmission())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('contact'));

    $contactSubmission = ContactSubmission::query()->sole();

    expect($contactSubmission)
        ->delivery_status->toBe(ContactDeliveryStatus::NotConfigured)
        ->delivery_error->toBe('Er zijn geen actieve beheerders die de notificatie kunnen ontvangen.');

    Notification::assertNothingSent();
});

test('a notification dispatch failure is recorded while the stored request still succeeds', function () {
    config(['mail.default' => 'smtp']);
    $administrator = User::factory()->create();
    $administrator->assignRole(Role::Admin->value);
    Notification::shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('SMTP is unavailable.'));

    $this->post(route('contact.store'), validContactSubmission())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('contact'));

    $contactSubmission = ContactSubmission::query()->sole();

    expect($contactSubmission)
        ->delivery_status->toBe(ContactDeliveryStatus::Failed)
        ->delivery_error->toBe('De e-mailnotificatie kon niet worden verzonden. Controleer de applicatielogs.')
        ->delivery_attempted_at->not->toBeNull()
        ->delivered_at->toBeNull();
});

test('a delivered administrator notification updates the delivery status', function () {
    $administrator = User::factory()->create();
    $administrator->assignRole(Role::Admin->value);
    $contactSubmission = ContactSubmission::factory()->create([
        'delivery_status' => ContactDeliveryStatus::Pending,
        'delivery_attempted_at' => null,
        'delivered_at' => null,
    ]);
    $notification = new ContactRequestReceived($contactSubmission);

    (new MarkContactNotificationAsSent)->handle(
        new NotificationSent($administrator, $notification, 'mail'),
    );

    expect($contactSubmission->refresh())
        ->delivery_status->toBe(ContactDeliveryStatus::Sent)
        ->delivery_attempted_at->not->toBeNull()
        ->delivered_at->not->toBeNull()
        ->delivery_error->toBeNull();
});

test('a definitively failed queued notification records manual follow-up', function () {
    $contactSubmission = ContactSubmission::factory()->create([
        'delivery_status' => ContactDeliveryStatus::Pending,
        'delivery_attempted_at' => null,
        'delivered_at' => null,
    ]);
    $notification = new ContactRequestReceived($contactSubmission);

    $notification->failed(new RuntimeException('SMTP is unavailable.'));

    expect($contactSubmission->refresh())
        ->delivery_status->toBe(ContactDeliveryStatus::Failed)
        ->delivery_attempted_at->not->toBeNull()
        ->delivered_at->toBeNull()
        ->delivery_error->toBe('De e-mailnotificatie kon niet worden verzonden. Controleer de applicatielogs.');
});

test('the mail notification replies directly to the visitor', function () {
    $contactSubmission = ContactSubmission::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
    ]);

    $mailMessage = (new ContactRequestReceived($contactSubmission))
        ->toMail(User::factory()->make());

    expect($mailMessage->replyTo)
        ->toBe([['ada@example.test', 'Ada Lovelace']])
        ->and($mailMessage->subject)
        ->toBe('Nieuwe contactaanvraag van Ada Lovelace');
});

test('contact request validation is clear', function (array $override, string $field) {
    $this->post(route('contact.store'), validContactSubmission($override))
        ->assertSessionHasErrors($field);

    expect(ContactSubmission::query()->count())->toBe(0);
})->with([
    'name is required' => [['name' => ''], 'name'],
    'email is valid' => [['email' => 'not-an-email'], 'email'],
    'topic is known' => [['topic' => 'unknown'], 'topic'],
    'message has useful detail' => [['message' => 'Te kort'], 'message'],
    'consent is accepted' => [['consent' => false], 'consent'],
]);

test('the honeypot rejects automated submissions', function () {
    $this->post(route('contact.store'), validContactSubmission([
        'website' => 'https://spam.example',
    ]))->assertSessionHasErrors('website');

    expect(ContactSubmission::query()->count())->toBe(0);
});

test('contact submissions are rate limited per visitor', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('contact.store'), validContactSubmission([
            'email' => "ada{$attempt}@example.test",
        ]))->assertRedirect(route('contact'));
    }

    $this->post(route('contact.store'), validContactSubmission([
        'email' => 'sixth@example.test',
    ]))->assertTooManyRequests();

    expect(ContactSubmission::query()->count())->toBe(5);
});

/**
 * @param  array<string, mixed>  $override
 * @return array<string, mixed>
 */
function validContactSubmission(array $override = []): array
{
    return [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
        'topic' => ContactTopic::Projects->value,
        'message' => 'Ik wil graag met DDS samenwerken aan een educatief droneproject.',
        'consent' => true,
        'source_context' => 'partners-page',
        'website' => '',
        ...$override,
    ];
}
