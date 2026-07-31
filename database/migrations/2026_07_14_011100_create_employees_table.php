<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Funcionários (responsáveis por solicitações, consumo, movimentações). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('document')->nullable(); // CPF
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('sector_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
