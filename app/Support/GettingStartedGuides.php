<?php

namespace App\Support;

final readonly class GettingStartedGuides
{
    /**
     * @param  list<GettingStartedGuide>  $guides
     */
    private function __construct(private array $guides) {}

    public static function fromConfig(): self
    {
        /** @var list<array<string, mixed>> $entries */
        $entries = config('getting_started_guides.guides');

        $guides = array_map(
            fn (array $attributes): GettingStartedGuide => GettingStartedGuide::fromArray($attributes),
            $entries,
        );

        usort(
            $guides,
            static fn (GettingStartedGuide $left, GettingStartedGuide $right): int => $left->sortOrder <=> $right->sortOrder,
        );

        return new self($guides);
    }

    /**
     * @return list<GettingStartedGuide>
     */
    public function all(): array
    {
        return $this->guides;
    }

    public function find(string $slug): ?GettingStartedGuide
    {
        foreach ($this->guides as $guide) {
            if ($guide->slug === $slug) {
                return $guide;
            }
        }

        return null;
    }
}
