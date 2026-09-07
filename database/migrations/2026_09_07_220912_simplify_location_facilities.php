<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('locations')->whereNotNull('facilities')->orderBy('id')->eachById(function (object $location): void {
            $facilities = json_decode($location->facilities, true, flags: JSON_THROW_ON_ERROR);
            $facilities['power'] = (bool) ($facilities['power'] ?? false) || (bool) ($facilities['charging'] ?? false);
            unset($facilities['charging']);
            if (($facilities['catering'] ?? null) === 'available') {
                $facilities['catering'] = 'nearby';
            }
            if (in_array($facilities['wifi'] ?? null, ['available', 'staff_only'], true)) {
                $facilities['wifi'] = 'private';
            }
            $facilities['spectator_area'] = (bool) ($facilities['spectator_area'] ?? false) || (bool) ($facilities['spectator_seating'] ?? false);
            unset($facilities['spectator_seating']);

            DB::table('locations')->where('id', $location->id)->update(['facilities' => json_encode($facilities, JSON_THROW_ON_ERROR)]);
        });
    }

    public function down(): void
    {
        // Retain the consolidated values: the original separate flags cannot be recovered.
    }
};
