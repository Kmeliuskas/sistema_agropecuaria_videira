<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/** Usuário administrador inicial (acesso total). */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@wms.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin@123456'),
                'is_active' => true,
                'password_changed_at' => now(),
            ]
        );

        $adminRole = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $admin->assignRole($adminRole);
    }
}
