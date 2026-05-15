<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Super Admin ───────────────────────────────────────────────────────
        User::firstOrCreate(['email' => 'admin@apidocs.dev'], [
            'name'        => 'Admin',
            'password'    => Hash::make('Admin@1234'),
            'role'        => 'admin',
            'permissions' => User::defaultPermissions('admin'),
            'is_active'   => true,
        ]);

        // ── Editor (can write + run + AI) ─────────────────────────────────────
        User::firstOrCreate(['email' => 'editor@apidocs.dev'], [
            'name'        => 'Editor',
            'password'    => Hash::make('Editor@1234'),
            'role'        => 'editor',
            'permissions' => User::defaultPermissions('editor'),
            'is_active'   => true,
        ]);

        // ── Viewer (read-only) ─────────────────────────────────────────────────
        User::firstOrCreate(['email' => 'viewer@apidocs.dev'], [
            'name'        => 'Viewer',
            'password'    => Hash::make('Viewer@1234'),
            'role'        => 'viewer',
            'permissions' => User::defaultPermissions('viewer'),
            'is_active'   => true,
        ]);

        $this->command->info('✅ Default users seeded:');
        $this->command->table(
            ['Email', 'Password', 'Role'],
            [
                ['admin@apidocs.dev',  'Admin@1234',  'admin'],
                ['editor@apidocs.dev', 'Editor@1234', 'editor'],
                ['viewer@apidocs.dev', 'Viewer@1234', 'viewer'],
            ]
        );
    }
}
