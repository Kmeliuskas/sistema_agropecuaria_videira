<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Database\Seeder;

class WarehouseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'FIS', 'name' => 'Físico', 'description' => 'Almoxarifado físico tradicional de armazenamento'],
            ['code' => 'PROD', 'name' => 'Produção', 'description' => 'Almoxarifado de insumos e matérias-primas para produção'],
            ['code' => 'OBRA', 'name' => 'Obra', 'description' => 'Almoxarifado de canteiro de obras e projetos'],
            ['code' => 'TRANS', 'name' => 'Trânsito', 'description' => 'Almoxarifado temporário de trânsito/transferência'],
        ];

        foreach ($types as $data) {
            WarehouseType::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
        }

        // Mapeia os registros de almoxarifados existentes para os novos IDs
        $typeMapping = [
            'physical' => 'FIS',
            'production' => 'PROD',
            'obra' => 'OBRA',
            'transit' => 'TRANS',
        ];

        foreach ($typeMapping as $oldType => $code) {
            $typeObj = WarehouseType::where('code', $code)->first();
            if ($typeObj) {
                Warehouse::where('type', $oldType)
                    ->whereNull('warehouse_type_id')
                    ->update(['warehouse_type_id' => $typeObj->id]);
            }
        }
    }
}
