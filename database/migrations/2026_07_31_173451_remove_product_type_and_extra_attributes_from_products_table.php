<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = Schema::getColumns('products');
            $existing = collect($columns)->pluck('name')->toArray();

            $toDrop = array_filter([
                'product_type'      => in_array('product_type', $existing),
                'extra_attributes'  => in_array('extra_attributes', $existing),
            ], fn ($keep) => $keep);

            foreach (array_keys($toDrop) as $column) {
                $table->dropColumn($column);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type', 30)->nullable()->after('name');
            $table->json('extra_attributes')->nullable()->after('description');
        });
    }
};
