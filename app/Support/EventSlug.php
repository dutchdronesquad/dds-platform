<?php

namespace App\Support;

use App\Actions\Admin\DuplicateEvent;
use App\Models\Event;
use Illuminate\Support\Str;

final class EventSlug
{
    private const int MAX_LENGTH = 255;

    /** Room kept free after the title for "-Y-m-d" and a "-999" sequence. */
    private const int SUFFIX_RESERVE = 15;

    /**
     * Build a unique "{title}-{Y-m-d}" slug, adding "-2", "-3", … when it is taken.
     */
    public static function generate(string $title, string $startsAtDate, ?int $ignoreEventId = null): string
    {
        return self::unique(self::titleSlug($title).'-'.$startsAtDate, $ignoreEventId);
    }

    /**
     * Find the first free slug for the given base, appending a sequence when needed.
     */
    public static function unique(string $baseSlug, ?int $ignoreEventId = null, int $firstSequence = 1): string
    {
        $sequence = $firstSequence;

        do {
            $suffix = $sequence === 1 ? '' : '-'.$sequence;
            $slug = Str::limit($baseSlug, self::MAX_LENGTH - Str::length($suffix), '').$suffix;
            $sequence++;
        } while (
            Event::query()
                ->where('slug', $slug)
                ->when($ignoreEventId !== null, fn ($query) => $query->whereKeyNot($ignoreEventId))
                ->exists()
        );

        return $slug;
    }

    /**
     * Whether the slug was derived from the event title and a start date rather than chosen by hand.
     */
    public static function isGenerated(Event $event): bool
    {
        return preg_match(self::generatedPattern($event->title), $event->slug) === 1;
    }

    /**
     * The slug without its trailing sequence, so duplicates can count on from the original.
     */
    public static function withoutSequence(Event $event): string
    {
        if (! self::isGenerated($event)) {
            return $event->slug;
        }

        return preg_replace('/(-\d{4}-\d{2}-\d{2})-\d+$/', '$1', $event->slug) ?? $event->slug;
    }

    private static function titleSlug(string $title): string
    {
        $title = Str::endsWith($title, DuplicateEvent::COPY_SUFFIX)
            ? Str::beforeLast($title, DuplicateEvent::COPY_SUFFIX)
            : $title;

        return rtrim(Str::limit(Str::slug($title) ?: 'event', self::MAX_LENGTH - self::SUFFIX_RESERVE, ''), '-');
    }

    private static function generatedPattern(string $title): string
    {
        return '/^'.preg_quote(self::titleSlug($title), '/').'-\d{4}-\d{2}-\d{2}(-\d+)?$/';
    }
}
