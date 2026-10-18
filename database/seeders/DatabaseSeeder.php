<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Bikin role yang dibutuhkan (kalau belum ada)
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $rolePanelUser  = Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);
        $roleKurir      = Role::firstOrCreate(['name' => 'kurir', 'guard_name' => 'web']);
        $roleOwner      = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);

        $admin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );
        $admin->syncRoles([$roleSuperAdmin, $rolePanelUser]);

        $hanung = User::updateOrCreate(
            ['email' => 'hanung@gmail.com'],
            [
                'name' => 'Hanung',
                'password' => bcrypt('hanung1234'),
                'role' => 'kurir',
            ]
        );
        $hanung->syncRoles([$roleKurir, $rolePanelUser]);

        $fadli = User::updateOrCreate(
            ['email' => 'fadli@gmail.com'],
            [
                'name' => 'Fadli',
                'password' => bcrypt('fadli1234'),
                'role' => 'owner',
            ]
        );
        $fadli->syncRoles([$roleOwner, $roleSuperAdmin, $rolePanelUser]);
    }
}
