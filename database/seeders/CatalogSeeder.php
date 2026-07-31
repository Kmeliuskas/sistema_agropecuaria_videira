<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/** Dados de catálogo iniciais (unidades, categorias, marcas, almoxarifados). */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'UN', 'name' => 'Unidade', 'symbol' => 'un', 'is_active' => true],
            ['code' => 'KG', 'name' => 'Quilograma', 'symbol' => 'kg', 'is_active' => true],
            ['code' => 'M', 'name' => 'Metro', 'symbol' => 'm', 'is_active' => true],
            ['code' => 'L', 'name' => 'Litro', 'symbol' => 'L', 'is_active' => true],
            ['code' => 'CX', 'name' => 'Caixa', 'symbol' => 'cx', 'is_active' => true],
            ['code' => 'PC', 'name' => 'Peça', 'symbol' => 'pc', 'is_active' => true],
        ];
        foreach ($units as $u) {
            Unit::firstOrCreate(['code' => $u['code']], $u);
        }

        $categories = [
            ['code' => 'MAT', 'name' => 'Matéria-prima', 'color' => '#3b82f6'],
            ['code' => 'EPI', 'name' => 'EPIs', 'color' => '#ef4444'],
            ['code' => 'FER', 'name' => 'Ferramentas', 'color' => '#f59e0b'],
            ['code' => 'ELE', 'name' => 'Material Elétrico', 'color' => '#8b5cf6'],
            ['code' => 'LIM', 'name' => 'Limpeza', 'color' => '#10b981'],
        ];
        foreach ($categories as $c) {
            Category::firstOrCreate(['code' => $c['code']], $c);
        }

        $brands = ['Bosch', '3M', 'WEG', 'Siemens', 'Tigre', 'Vonder'];
        foreach ($brands as $b) {
            Brand::firstOrCreate(['code' => str()->slug($b), 'name' => $b], ['code' => str()->slug($b), 'name' => $b, 'is_active' => true]);
        }

        $warehouses = [
            ['code' => 'CD', 'name' => 'Almoxarifado Central', 'type' => 'physical', 'is_default' => true, 'is_active' => true],
            ['code' => 'FIL01', 'name' => 'Filial São Paulo', 'type' => 'physical', 'is_active' => true],
            ['code' => 'PROD', 'name' => 'Produção', 'type' => 'production', 'is_active' => true],
            ['code' => 'OBRA01', 'name' => 'Obra Centro', 'type' => 'obra', 'is_active' => true],
        ];
        foreach ($warehouses as $w) {
            Warehouse::firstOrCreate(['code' => $w['code']], $w);
        }
    }
}
