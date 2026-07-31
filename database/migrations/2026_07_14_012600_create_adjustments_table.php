<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ajustes de estoque (erro, quebra, perda, roubo, vencimento, correção). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('reason'); // erro, quebra, perda, roubo, vencimento, correcao
            $table->decimal('quantity', 15, 4); // positivo (ganho) ou negativo (perda)
            $table->decimal('balance_before', 15, 4)->default(0);
            $table->decimal('balance_after', 15, 4)->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('observation')->nullable();
            $table->foreignId('movement_id')->nullable()->constrained('movements')->nullOnDelete();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'warehouse_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustments');
    }
};
