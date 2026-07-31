<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventários (geral, parcial, rotativo, por categoria, por localização,
 * por lote). Ao finalizar, gera ajustes automáticos pelas diferenças.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('type'); // general, partial, rotating, by_category, by_location, by_lot
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description')->nullable();
            $table->string('status')->default('draft'); // draft, in_progress, finalized, cancelled
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->integer('items_count')->default(0);
            $table->integer('counted_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
