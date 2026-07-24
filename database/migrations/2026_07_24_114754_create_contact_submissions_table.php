<?php

use App\Enums\ContactDeliveryStatus;
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
        Schema::create('contact_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 254);
            $table->string('topic', 50)->index();
            $table->text('message');
            $table->timestamp('consented_at');
            $table->string('source_context')->nullable();
            $table->string('delivery_status', 30)
                ->default(ContactDeliveryStatus::Pending->value);
            $table->timestamp('delivery_attempted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('delivery_error')->nullable();
            $table->timestamps();

            $table->index(['delivery_status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_submissions');
    }
};
