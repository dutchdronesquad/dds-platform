<?php

namespace App\Enums;

enum ContactDeliveryStatus: string
{
    case Pending = 'pending';
    case NotConfigured = 'not_configured';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In wachtrij',
            self::NotConfigured => 'Opvolging nodig',
            self::Sent => 'E-mail verzonden',
            self::Failed => 'Verzending mislukt',
        };
    }
}
