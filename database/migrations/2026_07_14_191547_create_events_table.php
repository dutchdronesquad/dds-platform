<?php

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')
                ->nullable()
                ->index()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->index()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('location_id')
                ->index()
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('season_id')
                ->nullable()
                ->index()
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('cover_image_id')
                ->nullable()
                ->index()
                ->constrained('media_assets')
                ->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content')->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->enum('status', EventStatus::cases())->default(EventStatus::Draft->value);
            $table->enum('type', EventType::cases())->default(EventType::Other->value);
            $table->unsignedInteger('price_cents')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestampTz('registration_opens_at')->nullable();
            $table->timestampTz('registration_deadline_at')->nullable();
            $table->enum('registration_status', EventRegistrationStatus::cases())
                ->default(EventRegistrationStatus::Closed->value);
            $table->text('registration_url')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['registration_status', 'registration_deadline_at']);
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
