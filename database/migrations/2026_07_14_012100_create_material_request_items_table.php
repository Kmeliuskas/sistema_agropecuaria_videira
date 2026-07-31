<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Itens da solicitação de materiais. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity_requested', 15, 4);
            $table->decimal('quantity_approved', 15, 4)->default(0);
            $table->decimal('quantity_delivered', 15, 4)->default(0);
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->index('material_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_request_items');
    }
};
