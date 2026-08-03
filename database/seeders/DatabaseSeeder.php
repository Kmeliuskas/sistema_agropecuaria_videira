<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ordem: permissões -> catálogo -> usuário admin.
     * Model events (audit) são desligados durante o seed para não poluir o log.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            //WarehouseTypeSeeder::class,
            //CatalogSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
