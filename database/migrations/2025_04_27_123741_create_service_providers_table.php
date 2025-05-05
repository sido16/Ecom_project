<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceProvidersTable extends Migration
{
    public function up()
    {
        Schema::create('service_providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('skill_domain_id');
            $table->text('description')->nullable();
            $table->decimal('starting_price', 8, 2)->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('skill_domain_id')->references('id')->on('skill_domains')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_providers');
    }
}