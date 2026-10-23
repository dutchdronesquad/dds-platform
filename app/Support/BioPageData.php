<?php

namespace App\Support;

use App\Enums\EventStatus;
use App\Models\Article;
use App\Models\Event;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * @phpstan-type BioItem array{title: string, href: string, note: ?string, image: ?array{src: string, alt: string}, date?: string}
 */
final class BioPageData
{
    public function __construct(
        private PublicArticleData $articleData,
        private PublicEventData $eventData,
    ) {}

    /** @return array{featured: ?BioItem, news: list<BioItem>, events: list<BioItem>, links: list<BioItem>, socials: list<BioItem>} */
    public function forPage(): array
    {
        $featured = config('bio.featured');
        $manual = $this->configuredItems($featured === null ? [] : [$featured]);
        $links = $this->configuredItems(config('bio.links'));
        $socials = $this->configuredItems(config('bio.socials'));

        $news = Article::query()
            ->select(['id', 'slug', 'title', 'published_at', 'cover_image_id'])
            ->publiclyVisible()
            ->with(['coverImage.media'])
            ->latest('published_at')->latest('id')
            ->limit(3)->get()
            ->map(fn (Article $article): array => [
                'title' => $article->title,
                'href' => route('news.show', ['article' => $article->slug], false),
                'note' => 'Nieuws · '.$article->published_at?->timezone('Europe/Amsterdam')->settings(['locale' => 'nl'])->isoFormat('D MMMM YYYY'),
                'image' => $this->articleData->image($article),
            ])->all();

        $events = Event::query()
            ->select(['id', 'slug', 'title', 'starts_at', 'status', 'cover_image_id'])
            ->publiclyVisible()->upcoming()
            ->with(['coverImage.media'])
            ->limit(3)->get()
            ->map(fn (Event $event): array => [
                'title' => $event->title,
                'href' => route('events.show', ['event' => $event->slug], false),
                'note' => ($event->status === EventStatus::Cancelled ? 'Geannuleerd · ' : '')
                    .$event->starts_at->timezone('Europe/Amsterdam')->settings(['locale' => 'nl'])->isoFormat('dddd D MMMM YYYY'),
                'image' => $this->eventData->image($event),
                'date' => $event->starts_at->toIso8601String(),
            ])->all();

        $featured = $manual[0] ?? array_shift($news) ?? array_shift($events);
        $seen = [];
        /** @param list<BioItem> $items
         * @return list<BioItem>
         */
        $unique = function (array $items) use (&$seen): array {
            return array_values(array_filter($items, function (array $item) use (&$seen): bool {
                $key = $this->destinationKey($item['href']);
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;

                return true;
            }));
        };
        $unique($featured === null ? [] : [$featured]);

        return [
            'featured' => $featured,
            'news' => $unique($news),
            'events' => $unique($events),
            'links' => $unique($links),
            'socials' => $unique($socials),
        ];
    }

    /** @return list<BioItem> */
    private function configuredItems(mixed $entries): array
    {
        if (! is_array($entries) || ! array_is_list($entries)) {
            throw new InvalidArgumentException('Bio links must be a list.');
        }

        $safeUrl = function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! $this->isSafeUrl($value)) {
                $fail("The {$attribute} must be a root-relative path or a safe HTTPS URL.");
            }
        };
        $validator = Validator::make(['items' => $entries], [
            'items.*' => ['required', 'array:title,href,note,image,starts_at,ends_at'],
            'items.*.title' => ['required', 'string', 'max:200'],
            'items.*.href' => ['required', 'string', $safeUrl],
            'items.*.note' => ['nullable', 'string', 'max:300'],
            'items.*.image' => ['nullable', 'array:src,alt'],
            'items.*.image.src' => ['required_with:items.*.image', 'string', $safeUrl],
            'items.*.image.alt' => ['required_with:items.*.image', 'string', 'max:300'],
            'items.*.starts_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP'],
            'items.*.ends_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP'],
        ]);
        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->toJson());
        }

        /** @var list<array{title: string, href: string, note?: ?string, image?: ?array{src: string, alt: string}, starts_at?: ?string, ends_at?: ?string}> $entries */
        $items = [];
        $now = CarbonImmutable::now();
        foreach ($entries as $entry) {
            $startsAt = isset($entry['starts_at']) ? CarbonImmutable::parse($entry['starts_at']) : null;
            $endsAt = isset($entry['ends_at']) ? CarbonImmutable::parse($entry['ends_at']) : null;
            if ($startsAt !== null && $endsAt !== null && $endsAt->lte($startsAt)) {
                throw new InvalidArgumentException('Bio ends_at must be after starts_at.');
            }
            if (($startsAt !== null && $now->lt($startsAt)) || ($endsAt !== null && $now->gte($endsAt))) {
                continue;
            }
            $items[] = [
                'title' => $entry['title'],
                'href' => $entry['href'],
                'note' => $entry['note'] ?? null,
                'image' => $entry['image'] ?? null,
            ];
        }

        return $items;
    }

    private function isSafeUrl(string $url): bool
    {
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            return false;
        }
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }
        $parts = parse_url($url);

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && ($parts['scheme'] ?? null) === 'https'
            && ! isset($parts['user']) && ! isset($parts['pass']);
    }

    private function destinationKey(string $href): string
    {
        $parts = parse_url($href);
        $origin = isset($parts['host'])
            ? strtolower(($parts['scheme'] ?? 'https').'://'.$parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '')
            : rtrim((string) config('app.url'), '/');

        return $origin.rtrim($parts['path'] ?? '/', '/')
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
