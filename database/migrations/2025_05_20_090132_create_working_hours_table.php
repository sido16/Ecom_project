<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->onDelete('cascade');
            $table->tinyInteger('day')->unsigned()->comment('1-7 for Monday-Sunday');
            $table->time('time_from');
            $table->time('time_to');
            $table->boolean('is_open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hours');
    }
};