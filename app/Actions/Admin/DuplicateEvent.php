<?php

namespace App\Actions\Admin;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateEvent
{
    private const string COPY_SUFFIX = ' (kopie)';

    public function handle(Event $event): Event
    {
        return DB::transaction(function () use ($event): Event {
            $sourceEvent = Event::query()->lockForUpdate()->findOrFail($event->id);
            $duplicate = $sourceEvent->replicate(['created_by', 'updated_by']);

            $duplicate->fill([
                'title' => Str::limit(
                    $sourceEvent->title,
                    255 - Str::length(self::COPY_SUFFIX),
                    '',
                ).self::COPY_SUFFIX,
                'slug' => $this->uniqueSlug($sourceEvent->slug),
                'status' => EventStatus::Draft,
                'published_at' => null,
            ]);
            $duplicate->saveOrFail();

            return $duplicate;
        }, attempts: 3);
    }

    private function uniqueSlug(string $sourceSlug): string
    {
        $baseSlug = Str::limit($sourceSlug, 249, '').'-kopie';
        $candidate = $baseSlug;
        $sequence = 2;

        while (Event::query()->where('slug', $candidate)->exists()) {
            $suffix = '-'.$sequence;
            $candidate = Str::limit($baseSlug, 255 - Str::length($suffix), '').$suffix;
            $sequence++;
        }

        return $candidate;
    }
}
