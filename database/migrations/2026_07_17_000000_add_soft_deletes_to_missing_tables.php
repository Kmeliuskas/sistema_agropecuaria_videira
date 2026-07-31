<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona soft deletes (deleted_at) nas tabelas cujos models agora usam
 * o trait SoftDeletes: stock_balances, movements, material_request_items,
 * inventory_items e transfer_items.
 *
 * Observação: audit_logs foi intencionalmente excluído — é append-only por
 * design (conformidade) e não deve permitir exclusão.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'stock_balances',
            'movements',
            'material_request_items',
            'inventory_items',
            'transfer_items',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'stock_balances',
            'movements',
            'material_request_items',
            'inventory_items',
            'transfer_items',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
