<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Controle de lotes (quando control_batch=true no produto). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('lot_number');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('remaining', 15, 4)->default(0);
            $table->date('manufactured_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status')->default('open'); // open, partial, closed, expired
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'warehouse_id']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
