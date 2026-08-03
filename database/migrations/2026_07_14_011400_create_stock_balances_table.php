<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saldo por produto x almoxarifado. Os 6 saldos exigidos:
 * current (atual), reserved (reservado), available (disponível),
 * blocked (bloqueado), in_conferencia (em conferência), in_transit (em trânsito).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('current', 15, 4)->default(0);
            $table->decimal('reserved', 15, 4)->default(0);
            $table->decimal('available', 15, 4)->default(0);
            $table->decimal('blocked', 15, 4)->default(0);
            $table->decimal('in_conferencia', 15, 4)->default(0);
            $table->decimal('in_transit', 15, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'warehouse_id'], 'stock_balances_unique_idx');
            $table->index('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
