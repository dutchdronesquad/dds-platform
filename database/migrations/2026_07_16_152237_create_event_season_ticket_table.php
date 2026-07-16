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
        Schema::create('event_season_ticket', function (Blueprint $table) {
            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('season_ticket_id')
                ->index()
                ->constrained()
                ->cascadeOnDelete();

            $table->primary(['event_id', 'season_ticket_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_season_ticket');
    }
};
