<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceProviderSkillTable extends Migration
{
    public function up()
    {
        Schema::create('service_provider_skill', function (Blueprint $table) {
            $table->unsignedBigInteger('service_provider_id');
            $table->unsignedBigInteger('skill_id');
            $table->foreign('service_provider_id')->references('id')->on('service_providers')->onDelete('cascade');
            $table->foreign('skill_id')->references('id')->on('skills')->onDelete('cascade');
            $table->primary(['service_provider_id', 'skill_id']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_provider_skill');
    }
}