<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('trackdraw_project_id')->nullable();
            $table->string('trackdraw_snapshot_path')->nullable();
            $table->text('trackdraw_title')->nullable();
            $table->string('trackdraw_default_view', 2)->default('2d');
            $table->timestampTz('trackdraw_synced_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['trackdraw_project_id', 'trackdraw_snapshot_path', 'trackdraw_title', 'trackdraw_default_view', 'trackdraw_synced_at']);
        });
    }
};
