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
        Schema::create('service_provider_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_provider_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comment');
            $table->text('reply')->nullable();
            $table->foreignId('reply_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reply_created_at')->nullable();
            $table->timestamps();

            $table->index('service_provider_id');
            $table->index('user_id');
            $table->index('reply_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_reviews');
    }
};
