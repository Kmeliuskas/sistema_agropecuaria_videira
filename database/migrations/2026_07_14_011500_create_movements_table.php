<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kardex completo. Cada movimentação registra saldo anterior/posterior,
 * origem/destino, usuário, centro de custo e observação. Base para todos
 * os relatórios e auditable por padrão.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->string('type'); // MovementType value
            $table->string('reason')->nullable(); // motivo (consumo, ajuste-erro, ...)
            $table->string('source_type')->nullable(); // entrada: purchase, invoice, devolution...
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_destination_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('balance_before', 15, 4)->default(0);
            $table->decimal('balance_after', 15, 4)->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_number')->nullable(); // NF, pedido, etc
            $table->text('observation')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'occurred_at']);
            $table->index(['warehouse_id', 'type']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
