<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Anexos de produto (manual, ficha técnica, imagens). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('file'); // manual, datasheet, image, other
            $table->string('name');
            $table->string('path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attachments');
    }
};
