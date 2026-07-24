<?php

namespace App\Listeners;

use App\Enums\ContactDeliveryStatus;
use App\Notifications\ContactRequestReceived;
use Illuminate\Notifications\Events\NotificationSent;

final class MarkContactNotificationAsSent
{
    public function handle(NotificationSent $event): void
    {
        if (
            $event->channel !== 'mail'
            || ! $event->notification instanceof ContactRequestReceived
        ) {
            return;
        }

        $event->notification->contactSubmission
            ->newQuery()
            ->whereKey($event->notification->contactSubmission->getKey())
            ->where('delivery_status', '!=', ContactDeliveryStatus::Failed->value)
            ->update([
                'delivery_status' => ContactDeliveryStatus::Sent,
                'delivery_attempted_at' => now(),
                'delivered_at' => now(),
                'delivery_error' => null,
            ]);
    }
}
