<?php

use App\Enums\LocationEnvironment;
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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cover_image_id')
                ->nullable()
                ->index()
                ->constrained('media_assets')
                ->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->jsonb('description');
            $table->string('street');
            $table->string('house_number');
            $table->string('postal_code');
            $table->string('city');
            $table->char('country_code', 2)->default('NL');
            $table->enum('environment', LocationEnvironment::cases());
            $table->unsignedInteger('floor_size_square_metres')->nullable();
            $table->decimal('ceiling_height_metres', 5, 2)->nullable();
            $table->jsonb('facilities')->nullable();
            $table->text('website_url')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
