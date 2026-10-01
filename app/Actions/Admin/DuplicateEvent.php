<?php

namespace App\Actions\Admin;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Support\EventSlug;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateEvent
{
    public function handle(Event $event): Event
    {
        $sequence = 2;

        while (true) {
            try {
                return $this->duplicateWithinTransaction($event, $sequence);
            } catch (UniqueConstraintViolationException $exception) {
                if (! $this->isSlugCollision($exception)) {
                    throw $exception;
                }
            }
        }
    }

    private function duplicateWithinTransaction(Event $event, int &$sequence): Event
    {
        return DB::transaction(function () use ($event, &$sequence): Event {
            $sourceEvent = Event::query()->lockForUpdate()->findOrFail($event->id);
            $duplicate = $sourceEvent->replicate(['created_by', 'updated_by']);

            $duplicate->fill([
                'title' => Str::limit(
                    $sourceEvent->title,
                    255 - Str::length(EventSlug::COPY_SUFFIX),
                    '',
                ).EventSlug::COPY_SUFFIX,
                'slug' => $this->uniqueSlug($sourceEvent, $sequence),
                'status' => EventStatus::Draft,
                'published_at' => null,
            ]);
            $duplicate->saveOrFail();

            return $duplicate;
        }, attempts: 3);
    }

    /**
     * Continue the source's slug sequence ("-2", "-3", …) instead of appending "-kopie".
     */
    private function uniqueSlug(Event $sourceEvent, int &$sequence): string
    {
        $slug = EventSlug::unique(EventSlug::withoutSequence($sourceEvent), firstSequence: $sequence);
        $sequence = (int) Str::afterLast($slug, '-') + 1;

        return $slug;
    }

    private function isSlugCollision(UniqueConstraintViolationException $exception): bool
    {
        return in_array('slug', $exception->columns, true)
            || $exception->index === 'events_slug_unique';
    }
}
