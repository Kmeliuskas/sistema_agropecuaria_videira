<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Itens da transferência entre almoxarifados. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 15, 4);
            $table->decimal('quantity_received', 15, 4)->default(0);
            $table->text('observation')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('transfer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_items');
    }
};
