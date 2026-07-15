<?php

use App\Enums\ArticleCategory;
use App\Enums\ArticleStatus;
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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')
                ->nullable()
                ->index()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('cover_image_id')
                ->nullable()
                ->index()
                ->constrained('media_assets')
                ->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->timestampTz('published_at')->nullable();
            $table->enum('status', ArticleStatus::cases())->default(ArticleStatus::Draft->value);
            $table->enum('category', ArticleCategory::cases())->default(ArticleCategory::News->value);
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
