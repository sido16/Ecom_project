<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offered_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id')->constrained('studio')->onDelete('cascade');
            $table->foreignId('studio_service_id')->constrained('studio_services')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['studio_id', 'studio_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offered_services');
    }
};