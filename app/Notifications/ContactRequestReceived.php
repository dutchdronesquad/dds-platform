<?php

namespace App\Notifications;

use App\Enums\ContactDeliveryStatus;
use App\Models\ContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class ContactRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public ContactSubmission $contactSubmission)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nieuwe contactaanvraag van '.$this->contactSubmission->name)
            ->replyTo(
                $this->contactSubmission->email,
                $this->contactSubmission->name,
            )
            ->greeting('Nieuwe contactaanvraag')
            ->line('Onderwerp: '.$this->contactSubmission->topic->label())
            ->line('Naam: '.$this->contactSubmission->name)
            ->line('E-mail: '.$this->contactSubmission->email)
            ->line($this->contactSubmission->message)
            ->action(
                'Bekijk de contactaanvraag',
                route('admin.contact.show', $this->contactSubmission),
            );
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function failed(Throwable $exception): void
    {
        $this->contactSubmission->update([
            'delivery_status' => ContactDeliveryStatus::Failed,
            'delivery_attempted_at' => now(),
            'delivery_error' => 'De e-mailnotificatie kon niet worden verzonden. Controleer de applicatielogs.',
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contact_submission_id' => $this->contactSubmission->id,
        ];
    }
}
