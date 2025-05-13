<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('business_name');
            $table->enum('type', ['coworking', 'studio']);
            $table->string('phone_number', 50);
            $table->string('email', 255);
            $table->text('location')->nullable();
            $table->string('address', 100);
            $table->text('description')->nullable();
            $table->string('opening_hours', 255)->nullable();
            $table->string('picture', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};