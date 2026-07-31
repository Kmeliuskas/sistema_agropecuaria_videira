<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Reserva de estoque (OP, projeto, obra, cliente, manutenção). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->decimal('quantity', 15, 4);
            $table->string('reservable_type')->nullable(); // polimórfico: project, obra, op...
            $table->unsignedBigInteger('reservable_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active'); // active, released, consumed, expired
            $table->text('observation')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
