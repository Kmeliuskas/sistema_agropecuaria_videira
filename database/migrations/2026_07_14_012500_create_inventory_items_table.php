<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Itens contados no inventário (book x counted -> diferença -> ajuste). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->decimal('book_quantity', 15, 4)->default(0);
            $table->decimal('counted_quantity', 15, 4)->nullable();
            $table->decimal('difference', 15, 4)->default(0);
            $table->boolean('is_counted')->default(false);
            $table->foreignId('counter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();

            $table->unique(['inventory_id', 'product_id', 'warehouse_id'], 'inv_item_unique_idx');
            $table->index('inventory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
