<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Transferência entre almoxarifados (cabeçalho + itens). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('origin_warehouse_id')->constrained('warehouses');
            $table->foreignId('destination_warehouse_id')->constrained('warehouses');
            $table->string('status')->default('pending'); // pending, in_transit, received, cancelled
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observation')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'origin_warehouse_id', 'destination_warehouse_id'], 'transfers_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
