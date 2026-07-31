<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Controle por número de série (serialized). Histórico de passagem fica em
 * serial_number_histories (fase futura); aqui o estado atual do serial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('serial')->unique();
            $table->string('status')->default('in_stock'); // in_stock, in_use, sold, returned, scrapped
            $table->foreignId('lot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('current_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');
    }
};
