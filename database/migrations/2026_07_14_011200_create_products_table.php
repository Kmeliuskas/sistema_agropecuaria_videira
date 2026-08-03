<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cadastro de produtos. Modelo rico conforme especificação.
 * Campos de controle de lote/validade/serialização são flags booleanas;
 * os detalhes ficam em lotes/serial_numbers (fases futuras, mas o esqueleto
 * de colunas já suporta o comportamento).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // Identificação
            $table->id();
            $table->string('internal_code', 30)->unique();
            $table->string('barcode', 50)->nullable()->index();
            $table->string('qrcode', 100)->nullable();
            $table->string('name');
            $table->text('description')->nullable();

            // Classificação
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model')->nullable();
            $table->foreignId('unit_id')->constrained();

            // Controle de estoque (parâmetros)
            $table->decimal('min_stock', 15, 4)->default(0);
            $table->decimal('max_stock', 15, 4)->default(0);
            $table->decimal('current_stock', 15, 4)->default(0); // saldo consolidado
            $table->decimal('reserved_stock', 15, 4)->default(0);
            $table->decimal('available_stock', 15, 4)->default(0);

            // Custos
            $table->decimal('last_cost', 15, 4)->default(0);
            $table->decimal('average_cost', 15, 4)->default(0);
            $table->decimal('sale_price', 15, 4)->default(0);

            // Tributação
            $table->string('ncm', 10)->nullable();
            $table->string('cfop', 4)->nullable();
            $table->string('cst', 4)->nullable();

            // Controle
            $table->boolean('control_batch')->default(false);
            $table->boolean('control_expiry')->default(false);
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('expiry_alert_days')->default(30);
            $table->boolean('serialized')->default(false);
            $table->boolean('active')->default(true);

            // Localização padrão
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('aisle')->nullable();   // rua
            $table->string('corridor')->nullable();
            $table->string('shelf')->nullable();   // prateleira
            $table->string('level')->nullable();
            $table->string('position')->nullable();

            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'active']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
