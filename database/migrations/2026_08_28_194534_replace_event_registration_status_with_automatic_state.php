<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('registration_enabled')->default(false)->after('capacity');
            $table->boolean('registration_closed_manually')->default(false)->after('registration_enabled');
            $table->boolean('registration_full')->default(false)->after('registration_closed_manually');
            $table->boolean('registration_waitlist_enabled')->default(false)->after('registration_full');
        });

        $referenceTime = now();

        DB::table('events')
            ->whereIn('registration_status', ['open', 'full', 'waitlist'])
            ->update(['registration_enabled' => true]);

        DB::table('events')
            ->whereIn('registration_status', ['full', 'waitlist'])
            ->update(['registration_full' => true]);

        DB::table('events')
            ->where('registration_status', 'waitlist')
            ->update(['registration_waitlist_enabled' => true]);

        DB::table('events')
            ->where('registration_status', 'closed')
            ->whereNotNull('registration_url')
            ->where('registration_opens_at', '>', $referenceTime)
            ->update(['registration_enabled' => true]);

        Schema::table('events', function (Blueprint $table) {
            $table->index(['registration_enabled', 'registration_deadline_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['registration_enabled', 'registration_deadline_at']);
            $table->dropColumn([
                'registration_enabled',
                'registration_closed_manually',
                'registration_full',
                'registration_waitlist_enabled',
            ]);
        });
    }
};
