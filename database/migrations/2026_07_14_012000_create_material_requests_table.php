<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitação de materiais (fluxo: solicitacao -> aprovacao -> separacao ->
 * conferencia -> entrega -> finalizacao). Cabeçalho; itens em
 * material_request_items (próxima migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('solicitado'); // solicitado, aprovado, separdo, conferido, entregue, finalizado, cancelado
            $table->text('justification')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('observation')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'requester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_requests');
    }
};
