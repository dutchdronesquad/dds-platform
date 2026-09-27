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
        Schema::create('track_draw_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('api_key');
            $table->timestamps();
        });

        if (DB::getDriverName() === 'sqlite') {
            // Avoid rebuilding events: SQLite schema introspection drops existing enum CHECK constraints.
            DB::statement('ALTER TABLE events ADD COLUMN track_draw_connection_id INTEGER REFERENCES track_draw_connections(id) ON DELETE SET NULL');

            return;
        }

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('track_draw_connection_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE events DROP COLUMN track_draw_connection_id');
        } else {
            Schema::table('events', function (Blueprint $table) {
                $table->dropConstrainedForeignId('track_draw_connection_id');
            });
        }
        Schema::dropIfExists('track_draw_connections');
    }
};
