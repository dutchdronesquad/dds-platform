<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Support\MediaAssetPickerData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class MediaAssetPickerController extends Controller
{
    public function __construct(private MediaAssetPickerData $pickerData) {}

    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MediaAsset::class);

        $search = Str::substr($request->string('search')->trim()->toString(), 0, 100);

        return response()->json([
            'data' => $this->pickerData->search($search),
        ]);
    }
}
