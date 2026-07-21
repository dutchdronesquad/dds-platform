<?php

namespace App\Actions\Admin;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StoreMediaAsset
{
    /** @param array<string, string>|null $altText */
    public function handle(UploadedFile $file, ?array $altText): MediaAsset
    {
        return DB::transaction(function () use ($file, $altText): MediaAsset {
            $mediaAsset = MediaAsset::query()->create([
                'alt_text' => str_starts_with($file->getMimeType() ?: '', 'image/')
                    ? $altText
                    : null,
            ]);

            [$width, $height] = $this->imageDimensions($file);

            $mediaAsset
                ->addMedia($file)
                ->usingName($file->getClientOriginalName())
                ->withCustomProperties([
                    'width' => $width,
                    'height' => $height,
                ])
                ->toMediaCollection(MediaAsset::COLLECTION);

            return $mediaAsset->load('media');
        });
    }

    /** @return array{int|null, int|null} */
    private function imageDimensions(UploadedFile $file): array
    {
        if (! str_starts_with($file->getMimeType() ?: '', 'image/')) {
            return [null, null];
        }

        $dimensions = getimagesize($file->getRealPath());

        return $dimensions === false
            ? [null, null]
            : [$dimensions[0], $dimensions[1]];
    }
}
