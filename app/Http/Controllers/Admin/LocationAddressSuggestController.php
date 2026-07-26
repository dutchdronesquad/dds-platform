<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SuggestLocationAddressRequest;
use App\Support\AddressGeocoder;
use Illuminate\Http\JsonResponse;

final class LocationAddressSuggestController extends Controller
{
    public function __construct(private AddressGeocoder $geocoder) {}

    public function __invoke(SuggestLocationAddressRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->geocoder->suggest($request->validated('q')),
        ]);
    }
}
