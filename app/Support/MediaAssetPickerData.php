<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class MediaAssetPickerData
{
    /** @return list<array<string, mixed>> */
    public function search(string $search): array
    {
        $query = MediaAsset::query()
            ->select([
                'id',
                'alt_text',
                'archived_at',
                'created_at',
            ])
            ->with('media')
            ->available()
            ->withMimeType('like', 'image/%');

        if ($search !== '') {
            $searchPattern = '%'.Str::lower($search).'%';

            $query->where(function (Builder $query) use ($searchPattern): void {
                $query
                    ->whereRaw('LOWER(CAST(alt_text AS TEXT)) LIKE ?', [$searchPattern])
                    ->orWhereHas('media', function (Builder $mediaQuery) use ($searchPattern): void {
                        $mediaQuery
                            ->whereRaw('LOWER(name) LIKE ?', [$searchPattern])
                            ->orWhereRaw('LOWER(file_name) LIKE ?', [$searchPattern]);
                    });
            });
        }

        $mediaAssets = $query
            ->latest()
            ->limit(30)
            ->get();

        $results = [];

        foreach ($mediaAssets as $mediaAsset) {
            $results[] = $this->one($mediaAsset);
        }

        return $results;
    }

    /** @return array<string, mixed> */
    public function one(MediaAsset $mediaAsset): array
    {
        return [
            'id' => $mediaAsset->id,
            'filename' => $mediaAsset->filename(),
            'mimeType' => $mediaAsset->mimeType(),
            'url' => $mediaAsset->url(),
            'isImage' => $mediaAsset->isImage(),
            'width' => $mediaAsset->width(),
            'height' => $mediaAsset->height(),
            'altText' => $mediaAsset->alt_text ?? [],
            'archivedAt' => $mediaAsset->archived_at?->toIso8601String(),
        ];
    }
}
