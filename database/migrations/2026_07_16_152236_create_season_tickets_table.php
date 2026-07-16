<?php

use App\Enums\SeasonTicketSalesState;
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
        Schema::create('season_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('sales_state', SeasonTicketSalesState::cases())
                ->default(SeasonTicketSalesState::NotOffered->value)
                ->index();
            $table->timestampTz('sales_opens_at')->nullable();
            $table->timestampTz('sales_closes_at')->nullable();
            $table->text('registration_url')->nullable();
            $table->text('copy')->nullable();
            $table->unsignedInteger('price_cents')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('season_tickets');
    }
};
