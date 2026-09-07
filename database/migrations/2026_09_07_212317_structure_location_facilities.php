<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('locations')->whereNotNull('facilities')->orderBy('id')->eachById(function (object $location): void {
            $values = json_decode($location->facilities, true, flags: JSON_THROW_ON_ERROR);
            if (! array_is_list($values)) {
                return;
            }
            $facilities = [];
            foreach ($values as $value) {
                if (in_array($value, ['parking', 'catering', 'wifi'], true)) {
                    $facilities[$value] = $value === 'parking' ? 'free' : 'available';
                } elseif (in_array($value, ['power', 'toilets', 'tables_and_chairs', 'charging', 'spectator_seating', 'spectator_area', 'wheelchair_accessible', 'first_aid_aed', 'heating', 'ventilation'], true)) {
                    $facilities[$value] = true;
                } else {
                    $facilities['legacy'][] = $value;
                }
            }
            DB::table('locations')->where('id', $location->id)->update(['facilities' => json_encode((object) $facilities, JSON_THROW_ON_ERROR)]);
        });
    }

    public function down(): void
    {
        // Keep richer values: converting back to a list would discard facility details.
    }
};
