<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\StoreMediaAsset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaAssetRequest;
use App\Support\MediaAssetPickerData;
use Illuminate\Http\JsonResponse;

final class MediaAssetQuickUploadController extends Controller
{
    public function __construct(private MediaAssetPickerData $pickerData) {}

    public function __invoke(StoreMediaAssetRequest $request, StoreMediaAsset $storeMediaAsset): JsonResponse
    {
        $mediaAsset = $storeMediaAsset->handle($request->file('file'), $request->altText());

        return response()->json([
            'data' => $this->pickerData->one($mediaAsset),
        ], 201);
    }
}
