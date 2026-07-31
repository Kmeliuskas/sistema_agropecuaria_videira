<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Localização física do produto por almoxarifado (rua/corredor/prateleira/
 * nível/posição). Permite múltiplas posições por produto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('aisle')->nullable();
            $table->string('corridor')->nullable();
            $table->string('shelf')->nullable();
            $table->string('level')->nullable();
            $table->string('position')->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id'], 'pl_wh_unique_idx');
            $table->index(['warehouse_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_locations');
    }
};
