<?php

namespace App\Actions\Admin;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateEvent
{
    private const string COPY_SUFFIX = ' (kopie)';

    public function handle(Event $event): Event
    {
        $sequence = 1;

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
                    255 - Str::length(self::COPY_SUFFIX),
                    '',
                ).self::COPY_SUFFIX,
                'slug' => $this->uniqueSlug($sourceEvent->slug, $sequence),
                'status' => EventStatus::Draft,
                'published_at' => null,
            ]);
            $duplicate->saveOrFail();

            return $duplicate;
        }, attempts: 3);
    }

    private function uniqueSlug(string $sourceSlug, int &$sequence): string
    {
        $baseSlug = Str::limit($sourceSlug, 249, '').'-kopie';

        do {
            $suffix = $sequence === 1 ? '' : '-'.$sequence;
            $candidate = Str::limit($baseSlug, 255 - Str::length($suffix), '').$suffix;
            $sequence++;
        } while (Event::query()->where('slug', $candidate)->exists());

        return $candidate;
    }

    private function isSlugCollision(UniqueConstraintViolationException $exception): bool
    {
        return in_array('slug', $exception->columns, true)
            || $exception->index === 'events_slug_unique';
    }
}
