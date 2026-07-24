<?php

namespace Database\Factories;

use App\Enums\ContactDeliveryStatus;
use App\Enums\ContactTopic;
use App\Models\ContactSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactSubmission>
 */
class ContactSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'topic' => fake()->randomElement(ContactTopic::cases()),
            'message' => fake()->paragraphs(2, true),
            'consented_at' => now(),
            'source_context' => fake()->optional()->randomElement([
                'homepage',
                'partners',
                'events',
            ]),
            'delivery_status' => ContactDeliveryStatus::Sent,
            'delivery_attempted_at' => now(),
            'delivered_at' => now(),
            'delivery_error' => null,
        ];
    }

    public function followUpNeeded(): static
    {
        return $this->state(fn (): array => [
            'delivery_status' => ContactDeliveryStatus::NotConfigured,
            'delivery_attempted_at' => null,
            'delivered_at' => null,
            'delivery_error' => 'E-mailnotificaties zijn niet geconfigureerd.',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'delivery_status' => ContactDeliveryStatus::Failed,
            'delivery_attempted_at' => now(),
            'delivered_at' => null,
            'delivery_error' => 'De e-mailnotificatie kon niet worden verzonden. Controleer de applicatielogs.',
        ]);
    }
}
