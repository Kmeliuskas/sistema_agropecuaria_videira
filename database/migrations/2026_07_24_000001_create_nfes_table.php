<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('number', 20);
            $table->string('series', 10);
            $table->date('emission_date');
            $table->date('receipt_date')->nullable();
            $table->decimal('total_value', 12, 2)->default(0);
            $table->string('status', 20)->default('pending'); // pending, received, canceled
            $table->text('observation')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('nfe_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nfe_id');
            $table->foreign('nfe_id')->references('id')->on('nfes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->decimal('unit_value', 12, 4)->default(0);
            $table->decimal('total_value', 12, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfe_items');
        Schema::dropIfExists('nfes');
    }
};
