<?php

namespace App\Actions;

use App\Enums\ContactDeliveryStatus;
use App\Enums\Role;
use App\Models\ContactSubmission;
use App\Models\User;
use App\Notifications\ContactRequestReceived;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SubmitContactRequest
{
    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     topic: string,
     *     message: string,
     *     consent: bool,
     *     source_context: string|null
     * }  $data
     */
    public function handle(array $data): ContactSubmission
    {
        $contactSubmission = ContactSubmission::query()->create([
            ...Arr::only($data, [
                'name',
                'email',
                'topic',
                'message',
                'source_context',
            ]),
            'consented_at' => now(),
        ]);

        $administrators = User::query()
            ->select(['id', 'name', 'email', 'locale'])
            ->where('is_active', true)
            ->role(Role::Admin->value)
            ->get();

        if ($administrators->isEmpty()) {
            $contactSubmission->update([
                'delivery_status' => ContactDeliveryStatus::NotConfigured,
                'delivery_error' => 'Er zijn geen actieve beheerders die de notificatie kunnen ontvangen.',
            ]);

            return $contactSubmission;
        }

        if (! $this->mailIsConfigured()) {
            $contactSubmission->update([
                'delivery_status' => ContactDeliveryStatus::NotConfigured,
                'delivery_error' => 'E-mailnotificaties zijn niet geconfigureerd. Handmatige opvolging is nodig.',
            ]);

            return $contactSubmission;
        }

        try {
            Notification::send(
                $administrators,
                new ContactRequestReceived($contactSubmission),
            );
        } catch (Throwable $exception) {
            $contactSubmission->update([
                'delivery_status' => ContactDeliveryStatus::Failed,
                'delivery_attempted_at' => now(),
                'delivery_error' => 'De e-mailnotificatie kon niet worden verzonden. Controleer de applicatielogs.',
            ]);

            report($exception);
        }

        return $contactSubmission;
    }

    private function mailIsConfigured(): bool
    {
        return ! in_array(config('mail.default'), ['array', 'log'], true);
    }
}
