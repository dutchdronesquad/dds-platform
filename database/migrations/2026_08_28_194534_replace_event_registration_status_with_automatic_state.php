<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
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
            ->select([
                'id',
                'registration_opens_at',
                'registration_status',
                'registration_url',
            ])
            ->orderBy('id')
            ->eachById(function (object $event) use ($referenceTime): void {
                $wasOpen = $event->registration_status === 'open';
                $wasWaitlist = $event->registration_status === 'waitlist';
                $wasFull = $event->registration_status === 'full';
                $wasScheduled = $event->registration_status === 'closed'
                    && $event->registration_url !== null
                    && $event->registration_opens_at !== null
                    && Carbon::parse($event->registration_opens_at)->greaterThan($referenceTime);

                DB::table('events')
                    ->where('id', $event->id)
                    ->update([
                        'registration_enabled' => $wasOpen || $wasWaitlist || $wasFull || $wasScheduled,
                        'registration_full' => $wasWaitlist || $wasFull,
                        'registration_waitlist_enabled' => $wasWaitlist,
                    ]);
            });

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
