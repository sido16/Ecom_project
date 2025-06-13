<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavedServiceProvidersTable extends Migration
{
    public function up()
    {
        Schema::create('saved_service_providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('service_provider_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_provider_id')->references('id')->on('service_providers')->onDelete('cascade');

            $table->unique(['user_id', 'service_provider_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('saved_service_providers');
    }
}
?>
