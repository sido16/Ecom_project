<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
    
            // Foreign keys
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('wilaya_id')->nullable()->constrained('wilayas')->onDelete('set null');
            $table->foreignId('commune_id')->nullable()->constrained('communes')->onDelete('set null');
    
            // Customer info
            $table->string('full_name')->nullable();
            $table->string('phone_number')->nullable();
    
            // Order info
            $table->enum('status', ['pending', 'processing', 'delivered'])->default('pending');
            $table->timestamp('order_date')->useCurrent();
            $table->boolean('is_validated')->default(false);
    
            // Optional address
            $table->string('address')->nullable();
    
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};