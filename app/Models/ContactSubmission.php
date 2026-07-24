<?php

namespace App\Models;

use App\Enums\ContactDeliveryStatus;
use App\Enums\ContactTopic;
use Carbon\CarbonImmutable;
use Database\Factories\ContactSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property ContactTopic $topic
 * @property string $message
 * @property CarbonImmutable $consented_at
 * @property string|null $source_context
 * @property ContactDeliveryStatus $delivery_status
 * @property CarbonImmutable|null $delivery_attempted_at
 * @property CarbonImmutable|null $delivered_at
 * @property string|null $delivery_error
 */
final class ContactSubmission extends Model
{
    /** @use HasFactory<ContactSubmissionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'topic',
        'message',
        'consented_at',
        'source_context',
        'delivery_status',
        'delivery_attempted_at',
        'delivered_at',
        'delivery_error',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'delivery_status' => ContactDeliveryStatus::Pending->value,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'topic' => ContactTopic::class,
            'consented_at' => 'immutable_datetime',
            'delivery_status' => ContactDeliveryStatus::class,
            'delivery_attempted_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
        ];
    }
}
