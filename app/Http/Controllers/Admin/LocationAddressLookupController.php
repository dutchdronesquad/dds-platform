<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LookupLocationAddressRequest;
use App\Support\AddressGeocoder;
use Illuminate\Http\JsonResponse;

final class LocationAddressLookupController extends Controller
{
    public function __construct(private AddressGeocoder $geocoder) {}

    public function __invoke(LookupLocationAddressRequest $request): JsonResponse
    {
        $address = $this->geocoder->lookup($request->validated('id'));

        if ($address === null) {
            return response()->json([
                'message' => 'Kon de details van dit adres niet ophalen.',
            ], 422);
        }

        return response()->json(['data' => $address]);
    }
}
